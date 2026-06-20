<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number',
        'subject',
        'body',
        'sender_name',
        'sender_email',
        'status',
        'priority',
        'category',
        'ai_summary',
        'assigned_to',
        'message_id',
        'source',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot method to auto-generate ticket numbers.
     */
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $latest = static::max('id') ?? 0;
                $ticket->ticket_number = 'TKT-' . str_pad($latest + 1, 5, '0', STR_PAD_LEFT);
            }
            if (empty($ticket->assigned_to) && $ticket->status === 'new') {
                $aiAgent = User::firstOrCreate(
                    ['email' => 'ai.assistant@helpdesk.com'],
                    [
                        'name' => 'AI',
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                        'role' => 'agent',
                    ]
                );
                $ticket->assigned_to = $aiAgent->id;
            }
        });
    }

    /**
     * Get the agent assigned to this ticket.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the email logs for this ticket.
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Get the replies for this ticket.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Scope: only open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope: only unassigned tickets.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope: tickets assigned to a specific agent.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Get a human-readable priority label with color class.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'slate',
        };
    }

    /**
     * Get a human-readable status label with color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'resolved' => 'green',
            'closed' => 'slate',
            default => 'slate',
        };
    }

    /**
     * Get the timestamp of the last reply, or a fallback string if none.
     */
    public function getLastReplyAtAttribute(): string
    {
        $lastReply = $this->replies()->latest()->first();
        return $lastReply ? $lastReply->created_at->format('M d, Y · H:i') : 'No replies yet';
    }

    /**
     * Send a ticket reply via email to the customer.
     */
    public function sendReplyEmail(TicketReply $reply): void
    {
        if (empty($this->sender_email)) {
            return;
        }

        try {
            $sentMessage = Mail::send([], [], function ($message) use ($reply) {
                $message->to($this->sender_email, $this->sender_name)
                        ->replyTo(env('POSTMARK_INBOUND_ADDRESS'))
                        ->subject('Re: ' . $this->subject)
                        ->text($reply->body);

                if ($this->message_id) {
                    $cleanOriginalId = trim($this->message_id, " \t\n\r\0\x0B<>");
                    $message->getHeaders()->addTextHeader('In-Reply-To', '<' . $cleanOriginalId . '>');
                    $message->getHeaders()->addTextHeader('References', '<' . $cleanOriginalId . '>');
                }
            });

            $sentMessageId = null;
            if ($sentMessage && method_exists($sentMessage, 'getMessageId')) {
                $sentMessageId = trim($sentMessage->getMessageId(), " \t\n\r\0\x0B<>");
            } else {
                $sentMessageId = 'reply-' . \Illuminate\Support\Str::random(20) . '@domain.com';
            }

            \App\Models\EmailLog::create([
                'message_id' => $sentMessageId,
                'from' => config('mail.from.address'),
                'subject' => 'Re: ' . $this->subject,
                'status' => 'processed',
                'ticket_id' => $this->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Ticket model: Failed to send reply email', [
                'ticket_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
