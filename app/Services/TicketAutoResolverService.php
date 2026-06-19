<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TicketAutoResolverService
{
    /**
     * Attempt to auto-resolve a ticket using the knowledge base.
     */
    public function resolve(Ticket $ticket): void
    {
        // Don't auto-resolve if already resolved or closed
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            return;
        }

        $kbPath = base_path('Knowledge-base.md');
        if (!file_exists($kbPath)) {
            Log::warning("TicketAutoResolver: Knowledge-base.md not found at {$kbPath}");
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            return;
        }

        $kbContent = file_get_contents($kbPath);
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            Log::warning("TicketAutoResolver: Gemini API key not configured.");
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            return;
        }

        $prompt = $this->buildPrompt($ticket, $kbContent);

        try {
            $response = Http::retry(5, 2000)->post(
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
                                'resolved' => [
                                    'type' => 'boolean',
                                    'description' => 'True if the ticket can be fully resolved using ONLY the provided Knowledge Base. False otherwise.',
                                ],
                                'reply' => [
                                    'type' => 'string',
                                    'description' => 'A polite, complete, helpful support response addressing the query using the KB steps (if resolved is true, otherwise empty).',
                                ],
                            ],
                            'required' => ['resolved', 'reply'],
                        ],
                    ],
                ]
            );

            $resolved = false;

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                $result = json_decode($text, true);

                if ($result && isset($result['resolved'])) {
                    if ($result['resolved'] && !empty($result['reply'])) {
                        // Find or create AI Assistant user
                        $aiUser = User::firstOrCreate(
                            ['email' => 'ai.assistant@helpdesk.com'],
                            [
                                'name' => 'AI',
                                'password' => Hash::make(Str::random(32)),
                                'role' => 'agent',
                            ]
                        );

                        // Auto-resolve ticket
                        $ticket->update([
                            'status' => 'resolved',
                            'assigned_to' => $aiUser->id,
                        ]);

                        // Post reply
                        $reply = TicketReply::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $aiUser->id,
                            'body' => $result['reply'],
                        ]);

                        $ticket->sendReplyEmail($reply);

                        $resolved = true;
                        Log::info("TicketAutoResolver: Auto-resolved ticket {$ticket->ticket_number}");
                    }
                }
            }

            if (!$resolved) {
                $ticket->update([
                    'status' => 'open',
                    'assigned_to' => null,
                ]);
                Log::info("TicketAutoResolver: Ticket {$ticket->ticket_number} did not match knowledge base rules or API call failed. Setting status to open and unassigning.");
            }

        } catch (\Exception $e) {
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            Log::error("TicketAutoResolver: Exception during auto-resolution. Setting status to open.", [
                'message' => $e->getMessage(),
                'ticket' => $ticket->ticket_number,
            ]);
        }
    }

    /**
     * Build the auto-resolution prompt.
     */
    private function buildPrompt(Ticket $ticket, string $kbContent): string
    {
        return <<<PROMPT
You are an intelligent support ticket auto-resolver. Your task is to analyze if the customer's support ticket can be fully answered and resolved using ONLY the provided Knowledge Base content.

---
SUPPORT KNOWLEDGE BASE:
{$kbContent}
---

CUSTOMER TICKET DETAILS:
Subject: {$ticket->subject}
Message Body:
{$ticket->body}

---
INSTRUCTIONS:
1. Carefully compare the customer's query with the questions/answers in the Support Knowledge Base.
2. If the query is directly addressed in the Knowledge Base (e.g., forgotten password instructions, store hours, clearing cache, refund eligibility guidelines, etc.):
   - Set 'resolved' to true.
   - Write a complete, polite, and detailed support reply in 'reply' that gives the customer the exact steps and solution from the Knowledge Base. Address them politely.
3. If the query is NOT addressed in the Knowledge Base, is unrelated, asks for something else, or is unreadable gibberish:
   - Set 'resolved' to false.
   - Set 'reply' to an empty string.

Be conservative: do not make up any facts or steps not found in the Knowledge Base. If not sure, set 'resolved' to false.
PROMPT;
    }
}
