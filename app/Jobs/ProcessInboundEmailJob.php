<?php

namespace App\Jobs;

use App\Models\EmailLog;
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
use Illuminate\Support\Str;

class ProcessInboundEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $payload)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fromEmail = $this->payload['from_email'];
        $fromName = $this->payload['from_name'] ?? null;
        $subject = $this->payload['subject'] ?? '(No Subject)';
        $body = $this->payload['body'] ?? '(Empty body)';
        $messageId = $this->payload['message_id'] ?? '';
        $inReplyTo = $this->payload['in_reply_to'] ?? null;
        $references = $this->payload['references'] ?? null;

        Log::info('ProcessInboundEmailJob: Processing incoming email.', [
            'from_email' => $fromEmail,
            'subject'    => $subject,
            'message_id' => $messageId,
        ]);

        // 1. Duplicate check (just in case)
        if (!empty($messageId) && EmailLog::where('message_id', $messageId)->exists()) {
            Log::info('ProcessInboundEmailJob: Duplicate email skipped.', ['message_id' => $messageId]);
            return;
        }

        // 2. Thread detection
        $matchedTicket = $this->findExistingTicket($inReplyTo, $references, $subject);

        if ($matchedTicket) {
            $this->handleReply($matchedTicket, $fromEmail, $fromName, $subject, $body, $messageId);
            return;
        }

        // 3. Create a new ticket (starts in 'new' status)
        $ticket = Ticket::create([
            'subject'      => $subject,
            'body'         => $body,
            'sender_name'  => $fromName,
            'sender_email' => $fromEmail,
            'status'       => 'new',
            'priority'     => 'medium',
            'message_id'   => !empty($messageId) ? $messageId : null,
            'source'       => 'email',
        ]);

        // Log the processed email
        EmailLog::create([
            'message_id' => !empty($messageId) ? $messageId : 'postmark-' . now()->timestamp . '-' . Str::random(8),
            'from'       => $fromEmail,
            'subject'    => $subject,
            'status'     => 'processed',
            'ticket_id'  => $ticket->id,
        ]);

        // 4. Run AI classification & Auto-Resolution
        $this->processAI($ticket);
    }

    /**
     * Process the ticket with AI: Categorization and Auto-Resolution.
     */
    protected function processAI(Ticket $ticket): void
    {
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
            Log::warning("ProcessInboundEmailJob: Knowledge base file not found.");
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            return;
        }

        $kbContent = file_get_contents($kbPath);
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            Log::warning("ProcessInboundEmailJob: Gemini API key not configured.");
            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
            return;
        }

        $prompt = $this->buildPrompt($ticket->subject, $ticket->body, $kbContent);

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
                                    'enum' => ['Auth', 'Billing', 'Technical', 'General'],
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
                    // Update category
                    $ticket->update([
                        'category' => $result['category'],
                    ]);

                    if ($result['resolved'] && !empty($result['reply'])) {
                        // Scenario A: AI Can Resolve
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

                        $ticket->sendReplyEmail($reply);

                        Log::info("ProcessInboundEmailJob: Ticket {$ticket->ticket_number} auto-resolved by AI.");
                    } else {
                        // Scenario B: AI Cannot Resolve
                        $ticket->update([
                            'status' => 'open',
                            'assigned_to' => null,
                        ]);
                        Log::info("ProcessInboundEmailJob: Ticket {$ticket->ticket_number} categorized as {$result['category']} but not resolvable.");
                    }
                    return;
                }
            }

            // Fallback if API returned failure or invalid schema
            Log::warning("ProcessInboundEmailJob: Gemini API failed or returned invalid response.", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);

        } catch (\Throwable $e) {
            Log::error("ProcessInboundEmailJob: Exception during AI processing.", [
                'message' => $e->getMessage(),
                'ticket'  => $ticket->ticket_number,
            ]);

            $ticket->update([
                'status' => 'open',
                'assigned_to' => null,
            ]);
        }
    }

    /**
     * Build the prompt.
     */
    protected function buildPrompt(string $subject, string $body, string $kbContent): string
    {
        return <<<PROMPT
You are a support ticket classifier and resolver. Analyze this support query:

---
KNOWLEDGE BASE:
{$kbContent}
---

CUSTOMER QUERY:
Subject: {$subject}
Body:
{$body}

INSTRUCTIONS:
1. **category**: Classify into exactly one of: Auth, Billing, Technical, General.
2. **resolved**: Can this query be fully resolved using ONLY the provided Knowledge Base? (true/false).
3. **reply**: If resolved is true, generate a polite, direct, and complete response answering the query using the exact instructions from the Knowledge Base. If resolved is false, return an empty string.

Return JSON object: {"category": "...", "resolved": ..., "reply": "..."}
PROMPT;
    }

    /**
     * Thread detection helper.
     */
    protected function findExistingTicket(?string $inReplyTo, ?string $references, string $subject): ?Ticket
    {
        $targetIds = $this->parseMessageIds($inReplyTo, $references);

        if (!empty($targetIds)) {
            $ticket = Ticket::whereIn('message_id', $targetIds)->first();
            if ($ticket) {
                return $ticket;
            }

            $log = EmailLog::whereIn('message_id', $targetIds)
                ->whereNotNull('ticket_id')
                ->first();

            if ($log) {
                $ticket = Ticket::find($log->ticket_id);
                if ($ticket) {
                    return $ticket;
                }
            }
        }

        if (preg_match('/TKT-\d{5}/i', $subject, $matches)) {
            $ticketNumber = strtoupper($matches[0]);
            $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
            if ($ticket) {
                return $ticket;
            }
        }

        return null;
    }

    /**
     * Reply handler.
     */
    protected function handleReply(Ticket $ticket, string $fromEmail, ?string $fromName, string $subject, string $body, string $messageId): void
    {
        $customerUser = User::where('email', $fromEmail)->first();

        if (!$customerUser) {
            $customerUser = User::create([
                'name'     => $fromName ?? explode('@', $fromEmail)[0],
                'email'    => $fromEmail,
                'password' => Hash::make(Str::random(32)),
                'role'     => 'customer',
            ]);
        }

        TicketReply::create([
            'ticket_id'    => $ticket->id,
            'user_id'      => $customerUser->id,
            'body'         => $body,
            'message_type' => 'incoming',
        ]);

        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        EmailLog::create([
            'message_id' => !empty($messageId) ? $messageId : 'postmark-' . now()->timestamp . '-' . Str::random(8),
            'from'       => $fromEmail,
            'subject'    => $subject,
            'status'     => 'processed',
            'ticket_id'  => $ticket->id,
        ]);

        Log::info('ProcessInboundEmailJob: Reply appended to existing ticket.', [
            'ticket_number' => $ticket->ticket_number,
        ]);
    }

    /**
     * Helper to parse Message-IDs.
     */
    protected function parseMessageIds(?string $inReplyTo, ?string $references): array
    {
        $ids = [];

        if (!empty($inReplyTo)) {
            $cleaned = trim($inReplyTo, " \t\n\r\0\x0B<>");
            if (!empty($cleaned)) {
                $ids[] = $cleaned;
            }
        }

        if (!empty($references)) {
            $parts = preg_split('/\s+/', $references);
            foreach ($parts as $part) {
                $cleaned = trim($part, " \t\n\r\0\x0B<>");
                if (!empty($cleaned)) {
                    $ids[] = $cleaned;
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
