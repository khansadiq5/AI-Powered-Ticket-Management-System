<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use App\Models\Ticket;
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
            $cm = new ClientManager();
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
                $messageId = $message->getMessageId()?->toString() ?? $message->getMessageId();
                $fromAddress = $message->getFrom()[0]->mail ?? 'unknown@unknown.com';
                $fromName = $message->getFrom()[0]->personal ?? null;
                $subject = $message->getSubject()?->toString() ?? $message->getSubject() ?? '(No Subject)';
                $bodyText = $message->getTextBody() ?? strip_tags($message->getHTMLBody() ?? '');

                // Clean up the message ID
                $messageId = is_object($messageId) ? (string) $messageId : $messageId;
                $subject = is_object($subject) ? (string) $subject : $subject;

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

                // Create the ticket
                $ticket = Ticket::create([
                    'subject' => $subject,
                    'body' => $bodyText ?: '(Empty body)',
                    'sender_name' => $fromName,
                    'sender_email' => $fromAddress,
                    'status' => 'open',
                    'priority' => 'medium',
                    'message_id' => $messageId,
                ]);

                // AI Classification
                $classifier->classify($ticket);

                // Round-robin agent assignment
                $this->assignToAgent($ticket);

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
