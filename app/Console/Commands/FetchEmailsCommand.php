<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\TicketClassifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class FetchEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:fetch-emails
                            {--dry-run : Test the IMAP connection without creating tickets}
                            {--limit=50 : Maximum number of emails to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch unread emails from the support mailbox and convert them to tickets';

    /**
     * Execute the console command.
     */
    public function handle(TicketClassifierService $classifier): int
    {
        $config = config('services.imap');

        if (empty($config['username']) || empty($config['password'])) {
            $this->error('IMAP credentials not configured. Set IMAP_USERNAME and IMAP_PASSWORD in .env');
            return self::FAILURE;
        }

        $this->info('Connecting to IMAP server: ' . $config['host'] . ':' . $config['port']);

        try {
            $cm = app(ClientManager::class);
            $client = $cm->make([
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'],
                'validate_cert' => true,
                'username' => $config['username'],
                'password' => $config['password'],
                'protocol' => 'imap',
            ]);

            $client->connect();
        } catch (ConnectionFailedException $e) {
            $this->error('Failed to connect to IMAP server: ' . $e->getMessage());
            Log::error('FetchEmails: IMAP connection failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('IMAP error: ' . $e->getMessage());
            Log::error('FetchEmails: IMAP error', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $this->info('Connected successfully. Fetching unread messages...');

        $folder = $client->getFolder($config['folder'] ?? 'INBOX');

        if (!$folder) {
            $this->error('Could not open folder: ' . ($config['folder'] ?? 'INBOX'));
            return self::FAILURE;
        }

        $messages = $folder->messages()
            ->unseen()
            ->limit((int) $this->option('limit'))
            ->get();

        $count = $messages->count();
        $this->info("Found {$count} unread message(s).");

        if ($count === 0) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — listing messages without creating tickets:');
            foreach ($messages as $message) {
                $this->line("  • [{$message->getMessageId()}] {$message->getSubject()} — from {$message->getFrom()[0]->mail}");
            }
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $created = 0;
        $duplicates = 0;
        $failures = 0;

        foreach ($messages as $message) {
            try {
                $messageId = $message->getMessageId();
                if (is_object($messageId) && method_exists($messageId, 'toString')) {
                    $messageId = $messageId->toString();
                } else {
                    $messageId = (string) $messageId;
                }

                $fromAddress = $message->getFrom()[0]->mail ?? 'unknown@unknown.com';
                $fromName = $message->getFrom()[0]->personal ?? null;

                $subject = $message->getSubject() ?? '(No Subject)';
                if (is_object($subject) && method_exists($subject, 'toString')) {
                    $subject = $subject->toString();
                } else {
                    $subject = (string) $subject;
                }

                $bodyText = $message->getTextBody() ?? strip_tags($message->getHTMLBody() ?? '');

                // Check for duplicates
                if ($messageId && EmailLog::where('message_id', $messageId)->exists()) {
                    $duplicates++;
                    EmailLog::create([
                        'message_id' => $messageId,
                        'from' => $fromAddress,
                        'subject' => $subject,
                        'status' => 'duplicate',
                    ]);
                    $bar->advance();
                    continue;
                }

                // --- Threading / Reply Detection ---
                $inReplyTo = null;
                try {
                    $inReplyTo = $message->get('in_reply_to');
                } catch (\Throwable $e) {}

                if (empty($inReplyTo)) {
                    try {
                        $inReplyTo = $message->getHeaders()?->get('in-reply-to');
                    } catch (\Throwable $e) {}
                }

                if (empty($inReplyTo)) {
                    try {
                        $inReplyTo = $message->getHeaders()?->get('in_reply_to');
                    } catch (\Throwable $e) {}
                }

                if (empty($inReplyTo)) {
                    try {
                        $inReplyTo = $message->getInReplyTo();
                    } catch (\Throwable $e) {}
                }

                $references = [];
                $refVal = null;
                try {
                    $refVal = $message->get('references');
                } catch (\Throwable $e) {}

                if (empty($refVal)) {
                    try {
                        $refVal = $message->getHeaders()?->get('references');
                    } catch (\Throwable $e) {}
                }

                if (empty($refVal)) {
                    try {
                        $refVal = $message->getReferences();
                    } catch (\Throwable $e) {}
                }

                if (!empty($refVal)) {
                    if (is_array($refVal)) {
                        $references = $refVal;
                    } elseif (is_object($refVal)) {
                        if (method_exists($refVal, 'toString')) {
                            $references = preg_split('/\s+/', $refVal->toString());
                        } elseif (method_exists($refVal, 'toArray')) {
                            $references = $refVal->toArray();
                        } else {
                            $references = preg_split('/\s+/', (string)$refVal);
                        }
                    } else {
                        $references = preg_split('/\s+/', (string)$refVal);
                    }
                }

                $cleanId = function($id) {
                    if (empty($id)) return '';
                    if (is_object($id)) {
                        if (method_exists($id, 'toString')) {
                            $id = $id->toString();
                        } else {
                            $id = (string)$id;
                        }
                    }
                    return trim($id, " \t\n\r\0\x0B<>");
                };

                $targetIds = [];
                if (!empty($inReplyTo)) {
                    $targetIds[] = $cleanId($inReplyTo);
                }
                foreach ($references as $ref) {
                    $cid = $cleanId($ref);
                    if (!empty($cid)) {
                        $targetIds[] = $cid;
                    }
                }
                $targetIds = array_unique(array_filter($targetIds));

                $matchedTicket = null;
                // 1. Check for references/in-reply-to message ID in tickets
                if (!empty($targetIds)) {
                    $matchedTicket = Ticket::whereIn('message_id', $targetIds)
                        ->orWhere(function ($query) use ($targetIds) {
                            foreach ($targetIds as $id) {
                                $query->orWhere('message_id', 'like', "%{$id}%");
                            }
                        })
                        ->first();
                }

                // 2. Check for references/in-reply-to message ID in email_logs
                if (!$matchedTicket && !empty($targetIds)) {
                    $log = EmailLog::whereIn('message_id', $targetIds)
                        ->whereNotNull('ticket_id')
                        ->first();
                    if ($log) {
                        $matchedTicket = Ticket::find($log->ticket_id);
                    }
                }

                // 3. Check for ticket number (e.g. TKT-xxxxx) in the subject
                if (!$matchedTicket && !empty($subject)) {
                    if (preg_match('/TKT-\d{5}/i', $subject, $matches)) {
                        $ticketNumber = strtoupper($matches[0]);
                        $matchedTicket = Ticket::where('ticket_number', $ticketNumber)->first();
                    }
                }

                if ($matchedTicket) {
                    // Find or create customer User
                    $customerUser = User::where('email', $fromAddress)->first();
                    if (!$customerUser) {
                        $customerUser = User::create([
                            'name' => $fromName ?? explode('@', $fromAddress)[0],
                            'email' => $fromAddress,
                            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                            'role' => 'customer',
                        ]);
                    }

                    // Save email body as a TicketReply
                    TicketReply::create([
                        'ticket_id' => $matchedTicket->id,
                        'user_id' => $customerUser->id,
                        'body' => $bodyText ?: '(Empty body)',
                    ]);

                    // Reopen the ticket / keep it open
                    $matchedTicket->update(['status' => 'open']);

                    // Log in EmailLog
                    EmailLog::create([
                        'message_id' => $messageId ?? 'no-id-' . now()->timestamp,
                        'from' => $fromAddress,
                        'subject' => $subject,
                        'status' => 'processed',
                        'ticket_id' => $matchedTicket->id,
                    ]);

                    // Mark as read
                    $message->setFlag('Seen');

                    $created++;
                    $bar->advance();
                    continue;
                }

                // Find or create the AI Agent
                $aiAgent = User::firstOrCreate(
                    ['email' => 'ai.assistant@helpdesk.com'],
                    [
                        'name' => 'AI',
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                        'role' => 'agent',
                    ]
                );

                // Create the ticket assigned to AI agent
                $ticket = Ticket::create([
                    'subject' => $subject,
                    'body' => $bodyText ?: '(Empty body)',
                    'sender_name' => $fromName,
                    'sender_email' => $fromAddress,
                    'status' => 'new',
                    'priority' => 'medium',
                    'message_id' => $messageId,
                    'assigned_to' => $aiAgent->id,
                    'source' => 'email',
                ]);

                // AI Classification (Queued via Redis)
                \App\Jobs\ClassifyTicketJob::dispatch($ticket);

                // Log success
                EmailLog::create([
                    'message_id' => $messageId ?? 'no-id-' . now()->timestamp,
                    'from' => $fromAddress,
                    'subject' => $subject,
                    'status' => 'processed',
                    'ticket_id' => $ticket->id,
                ]);

                // Mark as read
                $message->setFlag('Seen');

                $created++;
            } catch (\Exception $e) {
                $failures++;
                Log::error('FetchEmails: Failed to process message', [
                    'error' => $e->getMessage(),
                    'subject' => $subject ?? 'unknown',
                ]);

                EmailLog::create([
                    'message_id' => $messageId ?? 'error-' . now()->timestamp,
                    'from' => $fromAddress ?? 'unknown',
                    'subject' => $subject ?? 'unknown',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Processing complete:");
        $this->line("  ✅ Created: {$created}");
        $this->line("  ⏭️  Duplicates: {$duplicates}");
        $this->line("  ❌ Failures: {$failures}");

        Log::info('FetchEmails: Run completed', compact('created', 'duplicates', 'failures'));

        return self::SUCCESS;
    }

    /**
     * Assign a ticket to an agent using round-robin.
     */
    private function assignToAgent(Ticket $ticket): void
    {
        // Get all agents ordered by the number of open tickets (ascending)
        $agent = User::where('role', 'agent')
            ->withCount(['assignedTickets' => function ($query) {
                $query->whereIn('status', ['open', 'in_progress']);
            }])
            ->orderBy('assigned_tickets_count', 'asc')
            ->first();

        // Fall back to any admin if no agents exist
        if (!$agent) {
            $agent = User::where('role', 'admin')->first();
        }

        if ($agent) {
            $ticket->update(['assigned_to' => $agent->id]);
            Log::info("FetchEmails: Assigned {$ticket->ticket_number} to {$agent->name}");
        } else {
            Log::warning("FetchEmails: No agents available to assign {$ticket->ticket_number}");
        }
    }
}
