<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClassifyTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Ticket $ticket)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ticket = $this->ticket;
        if ($ticket->status !== 'new' && $ticket->status !== 'open') {
            return;
        }

        $ticket->update(['status' => 'processing']);

        // Find the knowledge base path
        $kbPath = null;
        $pathsToCheck = [
            storage_path('app/knowledge-base.md'),
            storage_path('app/Knowledge-base.md'),
            base_path('Knowledge-base.md'),
        ];

        foreach ($pathsToCheck as $path) {
            if (file_exists($path)) {
                $kbPath = $path;
                break;
            }
        }

        if (!$kbPath) {
            Log::warning("ClassifyTicketJob: Knowledge base file not found.");
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            return;
        }

        $kbContent = file_get_contents($kbPath);
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            Log::warning("ClassifyTicketJob: Gemini API key not configured.");
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            return;
        }

        $prompt = <<<PROMPT
You are a support ticket classifier and resolver. Analyze this support query:

---
KNOWLEDGE BASE:
{$kbContent}
---

CUSTOMER QUERY:
Subject: {$ticket->subject}
Body:
{$ticket->body}

INSTRUCTIONS:
1. **category**: Classify into exactly one of: General, Refund, Technical.
2. **resolved**: Can this query be fully resolved using ONLY the provided Knowledge Base? (true/false).
3. **reply**: If resolved is true, generate a polite, direct, and complete response answering the query using the exact instructions from the Knowledge Base. If resolved is false, return an empty string.

Return JSON object: {"category": "...", "resolved": ..., "reply": "..."}
PROMPT;

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
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'category' => [
                                    'type' => 'string',
                                    'enum' => ['General', 'Refund', 'Technical'],
                                ],
                                'resolved' => [
                                    'type' => 'boolean',
                                ],
                                'reply' => [
                                    'type' => 'string',
                                ],
                            ],
                            'required' => ['category', 'resolved', 'reply'],
                        ],
                    ],
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                $result = json_decode($text, true);

                if ($result && isset($result['category'], $result['resolved'])) {
                    $ticket->update([
                        'category' => $result['category'],
                    ]);

                    if ($result['resolved'] && !empty($result['reply'])) {
                        $aiUser = User::firstOrCreate(
                            ['email' => 'ai.assistant@helpdesk.com'],
                            [
                                'name' => 'AI',
                                'password' => Hash::make(Str::random(32)),
                                'role' => 'agent',
                            ]
                        );

                        $ticket->update([
                            'status' => 'resolved',
                            'assigned_to' => $aiUser->id,
                        ]);

                        $reply = TicketReply::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $aiUser->id,
                            'body' => $result['reply'],
                        ]);

                        // Outbound mail sending with robust logging & error handling
                        $userEmail = $ticket->sender_email;
                        Log::info('Attempting to send email to: ' . $userEmail);
                        try {
                            $sentMessage = Mail::to($userEmail)->send(new \App\Mail\TicketReplyMailable($ticket, $reply));
                            
                            $sentMessageId = null;
                            if ($sentMessage && method_exists($sentMessage, 'getMessageId')) {
                                $sentMessageId = trim($sentMessage->getMessageId(), " \t\n\r\0\x0B<>");
                            } else {
                                $sentMessageId = 'reply-' . \Illuminate\Support\Str::random(20) . '@domain.com';
                            }

                            \App\Models\EmailLog::create([
                                'message_id' => $sentMessageId,
                                'from' => '2203051050509@paruluniversity.ac.in',
                                'subject' => 'Re: ' . $ticket->subject,
                                'status' => 'processed',
                                'ticket_id' => $ticket->id,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Mail failed: ' . $e->getMessage());
                            
                            \App\Models\EmailLog::create([
                                'message_id' => 'fail-' . \Illuminate\Support\Str::random(20) . '@domain.com',
                                'from' => '2203051050509@paruluniversity.ac.in',
                                'subject' => 'Re: ' . $ticket->subject,
                                'status' => 'failed',
                                'error' => $e->getMessage(),
                                'ticket_id' => $ticket->id,
                            ]);
                        }

                        Log::info("ClassifyTicketJob: Ticket {$ticket->ticket_number} auto-resolved by AI.");
                    } else {
                        $ticket->update([
                            'status' => 'open',
                            'assigned_to' => null,
                        ]);
                        Log::info("ClassifyTicketJob: Ticket {$ticket->ticket_number} categorized as {$result['category']} but not resolvable.");
                    }
                    return;
                }
            }

            Log::warning("ClassifyTicketJob: Gemini API failed or returned invalid response.", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);

        } catch (\Throwable $e) {
            Log::error("ClassifyTicketJob: Exception during AI processing.", [
                'message' => $e->getMessage(),
                'ticket'  => $ticket->ticket_number,
            ]);

            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
        }
    }
}
