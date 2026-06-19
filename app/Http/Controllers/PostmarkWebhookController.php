<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundEmailJob;
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
     *  3. Dispatches ProcessInboundEmailJob to handle processing asynchronously.
     *  4. Returns 200 OK instantly.
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

        Log::info('PostmarkWebhook: Dispatching inbound email processing job.', [
            'from_email'  => $fromEmail,
            'subject'     => $subject,
            'message_id'  => $messageId,
        ]);

        // Dispatch background processing
        ProcessInboundEmailJob::dispatch([
            'from_email'  => $fromEmail,
            'from_name'   => $fromName,
            'subject'     => $subject,
            'body'        => $body,
            'message_id'  => $messageId,
            'in_reply_to' => $inReplyTo,
            'references'  => $references,
        ]);

        return response()->json([
            'status'  => 'queued',
            'message' => 'Email queued for processing.',
        ], 200);
    }

    // ======================================================================
    //  PRIVATE METHODS
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
}
