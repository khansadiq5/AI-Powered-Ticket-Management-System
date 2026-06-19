<?php

namespace App\Http\Controllers;

use App\Jobs\ClassifyTicketJob;
use App\Models\EmailLog;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostmarkWebhookController extends Controller
{
    /**
     * Handle an inbound email forwarded by Postmark's Inbound Webhook.
     *
     * Postmark sends a JSON payload on every inbound email. This method:
     *  1. Validates the shared-secret URL token.
     *  2. Parses the Postmark JSON (From, Subject, TextBody, Headers).
     *  3. Deduplicates via the Message-ID in email_logs.
     *  4. Detects threading (reply to existing ticket) or creates a new ticket.
     *  5. For new tickets, dispatches ClassifyTicketJob for AI classification.
     *  6. Returns 200 OK so Postmark does not retry.
     *
     * @see https://postmarkapp.com/developer/webhooks/inbound-webhook
     */
    public function handleInboundEmail(Request $request, string $token): JsonResponse
    {
        // ------------------------------------------------------------------
        // 1. VALIDATE WEBHOOK TOKEN
        // ------------------------------------------------------------------
        $expectedToken = config('services.postmark.webhook_token');

        if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
            Log::warning('PostmarkWebhook: Rejected — invalid or missing webhook token.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // ------------------------------------------------------------------
        // 2. LOG RAW PAYLOAD (for debugging — disable in production if noisy)
        // ------------------------------------------------------------------
        Log::info('PostmarkWebhook: Inbound email received.', [
            'ip'      => $request->ip(),
            'payload' => $request->except(['Attachments']), // exclude large attachment data
        ]);

        // ------------------------------------------------------------------
        // 3. PARSE POSTMARK JSON PAYLOAD
        // ------------------------------------------------------------------
        $fromEmail = $this->extractEmail($request->input('From', ''));
        $fromName  = $this->extractName($request->input('FromName', ''), $request->input('From', ''));
        $subject   = $request->input('Subject', '(No Subject)');
        $textBody  = $request->input('TextBody', '');
        $htmlBody  = $request->input('HtmlBody', '');
        $messageId = $request->input('MessageID', '');
        $body      = !empty($textBody) ? $textBody : strip_tags($htmlBody);

        if (empty($body)) {
            $body = '(Empty body)';
        }

        // Extract threading headers from the Headers array
        $headers     = collect($request->input('Headers', []));
        $inReplyTo   = $this->getHeaderValue($headers, 'In-Reply-To');
        $references  = $this->getHeaderValue($headers, 'References');

        Log::info('PostmarkWebhook: Parsed payload.', [
            'from_email'  => $fromEmail,
            'from_name'   => $fromName,
            'subject'     => $subject,
            'message_id'  => $messageId,
            'in_reply_to' => $inReplyTo,
            'references'  => $references,
        ]);

        // ------------------------------------------------------------------
        // 4. DUPLICATE CHECK (via Message-ID in email_logs)
        // ------------------------------------------------------------------
        if (!empty($messageId) && EmailLog::where('message_id', $messageId)->exists()) {
            Log::info('PostmarkWebhook: Duplicate email skipped.', ['message_id' => $messageId]);

            EmailLog::create([
                'message_id' => $messageId,
                'from'       => $fromEmail,
                'subject'    => $subject,
                'status'     => 'duplicate',
            ]);

            return response()->json(['status' => 'duplicate', 'message' => 'Email already processed.']);
        }

        // ------------------------------------------------------------------
        // 5. THREAD DETECTION — Is this a reply to an existing ticket?
        // ------------------------------------------------------------------
        $matchedTicket = $this->findExistingTicket($inReplyTo, $references, $subject);

        if ($matchedTicket) {
            return $this->handleReply($matchedTicket, $fromEmail, $fromName, $subject, $body, $messageId);
        }

        // ------------------------------------------------------------------
        // 6. NEW TICKET CREATION
        // ------------------------------------------------------------------
        return $this->handleNewTicket($fromEmail, $fromName, $subject, $body, $messageId);
    }

    // ======================================================================
    //  PRIVATE METHODS
    // ======================================================================

    /**
     * Attempt to find an existing ticket by threading headers or subject tag.
     *
     * Strategy (in order of precedence):
     *  1. Match In-Reply-To / References against tickets.message_id
     *  2. Match In-Reply-To / References against email_logs.message_id → ticket
     *  3. Match TKT-XXXXX pattern in the Subject line
     */
    private function findExistingTicket(?string $inReplyTo, ?string $references, string $subject): ?Ticket
    {
        // Build a list of candidate message IDs from threading headers
        $targetIds = $this->parseMessageIds($inReplyTo, $references);

        // --- Layer 1: Direct match on tickets.message_id ---
        if (!empty($targetIds)) {
            $ticket = Ticket::whereIn('message_id', $targetIds)->first();
            if ($ticket) {
                Log::info('PostmarkWebhook: Thread matched via tickets.message_id.', [
                    'ticket_number' => $ticket->ticket_number,
                ]);
                return $ticket;
            }
        }

        // --- Layer 2: Match via email_logs.message_id → ticket ---
        if (!empty($targetIds)) {
            $log = EmailLog::whereIn('message_id', $targetIds)
                ->whereNotNull('ticket_id')
                ->first();

            if ($log) {
                $ticket = Ticket::find($log->ticket_id);
                if ($ticket) {
                    Log::info('PostmarkWebhook: Thread matched via email_logs.message_id.', [
                        'ticket_number' => $ticket->ticket_number,
                        'log_id'        => $log->id,
                    ]);
                    return $ticket;
                }
            }
        }

        // --- Layer 3: Match TKT-XXXXX in the subject ---
        if (preg_match('/TKT-\d{5}/i', $subject, $matches)) {
            $ticketNumber = strtoupper($matches[0]);
            $ticket = Ticket::where('ticket_number', $ticketNumber)->first();

            if ($ticket) {
                Log::info('PostmarkWebhook: Thread matched via subject ticket number.', [
                    'ticket_number' => $ticketNumber,
                ]);
                return $ticket;
            }
        }

        return null;
    }

    /**
     * Handle an inbound email that is a reply to an existing ticket.
     *
     * Creates a customer user if needed, appends the message as a TicketReply
     * with message_type = 'incoming', reopens the ticket if resolved/closed,
     * and logs the email.
     */
    private function handleReply(Ticket $ticket, string $fromEmail, ?string $fromName, string $subject, string $body, string $messageId): JsonResponse
    {
        // Find or create the customer user
        $customerUser = User::where('email', $fromEmail)->first();

        if (!$customerUser) {
            $customerUser = User::create([
                'name'     => $fromName ?? explode('@', $fromEmail)[0],
                'email'    => $fromEmail,
                'password' => Hash::make(Str::random(32)),
                'role'     => 'customer',
            ]);

            Log::info('PostmarkWebhook: Created customer user for reply.', [
                'user_id' => $customerUser->id,
                'email'   => $fromEmail,
            ]);
        }

        // Save the reply as a TicketReply with message_type = incoming
        TicketReply::create([
            'ticket_id'    => $ticket->id,
            'user_id'      => $customerUser->id,
            'body'         => $body,
            'message_type' => 'incoming',
        ]);

        // Reopen the ticket if it was resolved or closed
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
            Log::info('PostmarkWebhook: Ticket reopened by customer reply.', [
                'ticket_number' => $ticket->ticket_number,
            ]);
        }

        // Log the processed email
        EmailLog::create([
            'message_id' => !empty($messageId) ? $messageId : 'postmark-' . now()->timestamp . '-' . Str::random(8),
            'from'       => $fromEmail,
            'subject'    => $subject,
            'status'     => 'processed',
            'ticket_id'  => $ticket->id,
        ]);

        Log::info('PostmarkWebhook: Reply appended to existing ticket.', [
            'ticket_number' => $ticket->ticket_number,
            'from'          => $fromEmail,
        ]);

        return response()->json([
            'status'        => 'reply_added',
            'ticket_number' => $ticket->ticket_number,
        ]);
    }

    /**
     * Handle an inbound email that starts a new ticket.
     *
     * Creates the ticket with source = 'email', dispatches ClassifyTicketJob
     * for AI classification + auto-resolution, and logs the email.
     */
    private function handleNewTicket(string $fromEmail, ?string $fromName, string $subject, string $body, string $messageId): JsonResponse
    {
        try {
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

            // Dispatch AI classification (runs async via queue)
            try {
                ClassifyTicketJob::dispatch($ticket);
            } catch (\Throwable $dispatchEx) {
                // Queue unavailable (e.g. Redis not installed) — set status to
                // 'open' so ticket is visible on admin/agent dashboards
                // (they filter out 'new' and 'processing').
                $ticket->update(['status' => 'open']);

                Log::warning('PostmarkWebhook: Failed to dispatch ClassifyTicketJob (queue may be unavailable). Ticket set to open.', [
                    'ticket_number' => $ticket->ticket_number,
                    'error'         => $dispatchEx->getMessage(),
                ]);
            }

            // Log the processed email
            EmailLog::create([
                'message_id' => !empty($messageId) ? $messageId : 'postmark-' . now()->timestamp . '-' . Str::random(8),
                'from'       => $fromEmail,
                'subject'    => $subject,
                'status'     => 'processed',
                'ticket_id'  => $ticket->id,
            ]);

            Log::info('PostmarkWebhook: New ticket created.', [
                'ticket_number' => $ticket->ticket_number,
                'from'          => $fromEmail,
                'subject'       => $subject,
            ]);

            return response()->json([
                'status'        => 'ticket_created',
                'ticket_number' => $ticket->ticket_number,
            ], 201);

        } catch (\Exception $e) {
            Log::error('PostmarkWebhook: Failed to create ticket.', [
                'error'   => $e->getMessage(),
                'from'    => $fromEmail,
                'subject' => $subject,
            ]);

            // Still return 200 to prevent Postmark from retrying indefinitely
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to process email.',
            ], 500);
        }
    }

    // ======================================================================
    //  HELPER METHODS
    // ======================================================================

    /**
     * Extract a clean email address from Postmark's "From" field.
     *
     * Handles formats like:
     *   - "John Doe <john@example.com>"
     *   - "john@example.com"
     */
    private function extractEmail(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return strtolower(trim($from));
    }

    /**
     * Extract a display name from the FromName or From field.
     */
    private function extractName(string $fromName, string $from): ?string
    {
        if (!empty($fromName)) {
            return trim($fromName);
        }

        // Try to extract name from "Name <email>" format
        if (preg_match('/^(.+?)\s*</', $from, $matches)) {
            $name = trim($matches[1], " \t\n\r\"'");
            if (!empty($name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Get the value of a specific header from Postmark's Headers array.
     *
     * Postmark sends headers as: [{"Name": "X-Header", "Value": "..."}, ...]
     */
    private function getHeaderValue($headers, string $headerName): ?string
    {
        $header = $headers->firstWhere('Name', $headerName);

        if ($header && !empty($header['Value'])) {
            return $header['Value'];
        }

        return null;
    }

    /**
     * Parse In-Reply-To and References headers into a clean array of message IDs.
     *
     * Message IDs typically look like: <abc123@domain.com>
     * References can contain multiple space-separated IDs.
     *
     * @return array<string> Cleaned message IDs (without angle brackets)
     */
    private function parseMessageIds(?string $inReplyTo, ?string $references): array
    {
        $ids = [];

        if (!empty($inReplyTo)) {
            $cleaned = trim($inReplyTo, " \t\n\r\0\x0B<>");
            if (!empty($cleaned)) {
                $ids[] = $cleaned;
            }
        }

        if (!empty($references)) {
            // References can contain multiple message IDs separated by whitespace
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
