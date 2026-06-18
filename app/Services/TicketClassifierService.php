<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketClassifierService
{
    /**
     * Classify a ticket using the Gemini API.
     *
     * Determines category, priority, and generates a short summary.
     */
    public function classify(Ticket $ticket): Ticket
    {
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            Log::warning('TicketClassifier: GEMINI_API_KEY not set, skipping classification.');
            return $ticket;
        }

        $prompt = $this->buildPrompt($ticket);

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
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
                                    'description' => 'Ticket category (must be one of: General, Refund, Technical)',
                                ],
                                'priority' => [
                                    'type' => 'string',
                                    'enum' => ['low', 'medium', 'high', 'urgent'],
                                ],
                                'summary' => [
                                    'type' => 'string',
                                    'description' => 'A 1-2 sentence summary of the issue',
                                ],
                            ],
                            'required' => ['category', 'priority', 'summary'],
                        ],
                    ],
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                $result = json_decode($text, true);

                if ($result && isset($result['category'], $result['priority'], $result['summary'])) {
                    $ticket->update([
                        'category' => in_array($result['category'], ['General', 'Refund', 'Technical'])
                            ? $result['category']
                            : 'General',
                        'priority' => in_array($result['priority'], ['low', 'medium', 'high', 'urgent'])
                            ? $result['priority']
                            : 'medium',
                        'ai_summary' => $result['summary'],
                    ]);

                    Log::info("TicketClassifier: Classified ticket {$ticket->ticket_number}", $result);
                } else {
                    Log::warning("TicketClassifier: Invalid JSON structure from Gemini", ['text' => $text]);
                }
            } else {
                Log::error("TicketClassifier: Gemini API returned {$response->status()}", [
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("TicketClassifier: Exception during classification", [
                'message' => $e->getMessage(),
                'ticket' => $ticket->ticket_number,
            ]);
        }

        return $ticket->fresh();
    }

    /**
     * Build the classification prompt.
     */
    private function buildPrompt(Ticket $ticket): string
    {
        $subject = $ticket->subject;
        $body = mb_substr($ticket->body, 0, 3000); // Limit body length for API

        return <<<PROMPT
You are a support ticket classifier for an organization's help desk. Analyze the following support email and determine:

1. **category**: Classify into exactly one of these 3 categories:
   - "Technical": for bugs, server/portal issues, logins, password reset, installation help, etc.
   - "Refund": for incorrect charges, refund requests, billing queries, payments, cancels.
   - "General": for general inquiries, feedback, feature requests, questions, or anything else.
2. **priority**: Assess urgency as "low", "medium", "high", or "urgent".
3. **summary**: Write a concise 1-2 sentence summary of the issue.

Guidelines for priority:
- "urgent": System down, data loss, security breach, blocking issue affecting many users
- "high": Major feature broken, significant impact on workflow, time-sensitive
- "medium": Standard issues, non-critical bugs, general questions
- "low": Feature requests, minor cosmetic issues, informational queries

---
**Subject:** {$subject}

**Body:**
{$body}
---

Return a JSON object with keys: category, priority, summary.
PROMPT;
    }
}
