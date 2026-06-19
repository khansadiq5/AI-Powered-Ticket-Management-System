<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * Display the Agent panel dashboard with assigned tickets.
     */
    public function index()
    {
        $user = Auth::user();

        $tickets = Ticket::assignedTo($user->id)
            ->whereNotIn('status', ['new', 'processing'])
            ->orderByRaw("CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END ASC")
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $tickets->count(),
            'open' => $tickets->where('status', 'open')->count(),
            'in_progress' => $tickets->where('status', 'in_progress')->count(),
            'resolved' => $tickets->where('status', 'resolved')->count(),
        ];

        return view('agent', compact('user', 'tickets', 'stats'));
    }

    /**
     * Display a single ticket detail.
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        // Agents can only view their own tickets
        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to view this ticket.');
        }

        $ticket->load('replies.user');

        return view('ticket-detail', compact('user', 'ticket'));
    }

    /**
     * Update the status of a ticket.
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to update this ticket.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $ticket->status,
                'status_label' => str_replace('_', ' ', $ticket->status),
                'message' => "Ticket status updated to " . str_replace('_', ' ', $ticket->status) . ".",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} status updated to {$validated['status']}.");
    }

    /**
     * Update the category of a ticket.
     */
    public function updateCategory(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to update this ticket.');
        }

        $validated = $request->validate([
            'category' => ['required', 'in:General,Refund,Technical'],
        ]);

        $ticket->update(['category' => $validated['category']]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $ticket->category,
                'message' => "Ticket category updated to {$ticket->category}.",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} category updated to {$validated['category']}.");
    }

    /**
     * Store a new reply for a ticket.
     */
    public function storeReply(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to reply to this ticket.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1'],
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $ticket->sendReplyEmail($reply);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully.',
                'reply' => [
                    'id' => $reply->id,
                    'body' => $reply->body,
                    'created_at_human' => $reply->created_at->diffForHumans(),
                    'user' => [
                        'name' => $user->name,
                        'role' => $user->role,
                        'initial' => strtoupper(substr($user->name, 0, 1)),
                    ]
                ]
            ]);
        }

        return back()->with('success', 'Reply posted successfully.');
    }

    /**
     * Polish a draft reply using the Gemini API.
     */
    public function polishReply(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to perform this action.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1'],
        ]);

        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API key is not configured.',
            ], 500);
        }

        $draft = $validated['body'];
        $subject = $ticket->subject;
        $ticketBody = $ticket->body;

        $prompt = "You are a professional customer support representative. An agent has written a draft reply to a support ticket.
Your task is to polish, improve, and refine this draft reply into a professional, clear, polite support response.

Guidelines:
1. Keep the response concise, restricting it strictly to exactly 2 to 3 lines/sentences.
2. Include a standard polite greeting (e.g., 'Hello,') and closing (e.g., 'Please let us know if you face any issues.').
3. Keep the core meaning, intent, and specific details from the agent's draft, adapting it to reference the customer's issue.
4. Output ONLY the polished response text. Do not include any introduction, explanations, markdown code blocks, or notes.

Customer's Ticket Subject: {$subject}
Customer's Ticket Body:
{$ticketBody}

Agent's Draft Reply to Polish:
{$draft}

Polished response (2-3 lines):";

        try {
            $response = Http::retry(5, 2000)->timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                $polished = trim($text);
                
                if (str_starts_with($polished, '```')) {
                    $polished = preg_replace('/^```[a-zA-Z]*\s*/', '', $polished);
                    $polished = preg_replace('/\s*```$/', '', $polished);
                    $polished = trim($polished);
                }

                return response()->json([
                    'success' => true,
                    'polished' => $polished,
                ]);
            } else {
                Log::error("PolishReply: Gemini API returned {$response->status()}", [
                    'body' => $response->body(),
                ]);

                $errorMessage = 'Failed to connect to Gemini API.';
                if ($response->status() === 503) {
                    $errorMessage = 'Gemini API is temporarily overloaded (High Demand). Please try again in a few seconds.';
                } elseif ($response->status() === 429) {
                    $errorMessage = 'Gemini API rate limit exceeded. Please wait a moment before trying again.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], $response->status());
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response ? $e->response->status() : 500;
            $body = $e->response ? $e->response->body() : $e->getMessage();

            Log::error("PolishReply: RequestException during polishing (Status {$status})", [
                'body' => $body,
            ]);

            $errorMessage = 'Failed to connect to Gemini API.';
            if ($status === 503) {
                $errorMessage = 'Gemini API is temporarily overloaded (High Demand). Please try again in a few seconds.';
            } elseif ($status === 429) {
                $errorMessage = 'Gemini API rate limit exceeded. Please wait a moment before trying again.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], $status);
        } catch (\Exception $e) {
            Log::error("PolishReply: Exception during polishing", [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while polishing the reply.',
            ], 500);
        }
    }

    /**
     * Summarize a ticket and its entire conversation history using the Gemini API.
     */
    public function summarize(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to perform this action.');
        }

        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API key is not configured.',
            ], 500);
        }

        $ticketBody = $ticket->body;
        $subject = $ticket->subject;
        $sender = $ticket->sender_name ?? $ticket->sender_email ?? 'Customer';

        // Get replies in chronological order
        $replies = $ticket->replies()->with('user')->oldest()->get();

        $conversation = "Customer ({$sender}): {$ticketBody}\n";
        foreach ($replies as $reply) {
            $roleName = ucfirst($reply->user->role);
            $conversation .= "{$roleName} ({$reply->user->name}): {$reply->body}\n";
        }

        $prompt = "You are a professional customer support assistant. Your task is to summarize the following support ticket and its entire conversation history.
Keep the summary concise (2-3 sentences), highlighting the customer's main issue, the actions taken so far, and the current resolution status.
Do not include any greeting, preamble, markdown code blocks, or system notes.

Ticket Subject: {$subject}

Conversation History:
{$conversation}

Concise Summary:";

        try {
            $response = Http::retry(5, 2000)->timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                $summary = trim($text);

                if (str_starts_with($summary, '```')) {
                    $summary = preg_replace('/^```[a-zA-Z]*\s*/', '', $summary);
                    $summary = preg_replace('/\s*```$/', '', $summary);
                    $summary = trim($summary);
                }

                // Save to database
                $ticket->ai_summary = $summary;
                $ticket->save();

                return response()->json([
                    'success' => true,
                    'summary' => $summary,
                ]);
            } else {
                Log::error("SummarizeTicket: Gemini API returned {$response->status()}", [
                    'body' => $response->body(),
                ]);

                $errorMessage = 'Failed to connect to Gemini API.';
                if ($response->status() === 503) {
                    $errorMessage = 'Gemini API is temporarily overloaded (High Demand). Please try again in a few seconds.';
                } elseif ($response->status() === 429) {
                    $errorMessage = 'Gemini API rate limit exceeded. Please wait a moment before trying again.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], $response->status());
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response ? $e->response->status() : 500;
            $body = $e->response ? $e->response->body() : $e->getMessage();

            Log::error("SummarizeTicket: RequestException during summarization (Status {$status})", [
                'body' => $body,
            ]);

            $errorMessage = 'Failed to connect to Gemini API.';
            if ($status === 503) {
                $errorMessage = 'Gemini API is temporarily overloaded (High Demand). Please try again in a few seconds.';
            } elseif ($status === 429) {
                $errorMessage = 'Gemini API rate limit exceeded. Please wait a moment before trying again.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], $status);
        } catch (\Exception $e) {
            Log::error("SummarizeTicket: Exception during summarization", [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the summary.',
            ], 500);
        }
    }
}
