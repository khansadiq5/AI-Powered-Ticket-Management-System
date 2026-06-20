<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AdminReplyMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The number of times the queued job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Ticket $ticket,
        public TicketReply $reply
    ) {}

    /**
     * Get the message envelope.
     *
     * - from: hardcoded to verified Postmark sender signature
     * - replyTo: Postmark inbound address so customer responses loop back
     * - subject: prefixed with "Re: [TKT-XXXXX]" for thread continuity
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address', 'khansadik5426@gmail.com'), config('mail.from.name', 'Helpdesk Support')),
            replyTo: [
                new Address(
                    config('services.postmark.inbound_address', '823f29a85a7d6de8df851a12b49d4795@inbound.postmarkapp.com'),
                    'Helpdesk Inbound'
                ),
            ],
            subject: 'Re: [' . $this->ticket->ticket_number . '] ' . $this->ticket->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: nl2br(e($this->reply->body)),
        );
    }

    /**
     * Get the message headers for Gmail conversation threading.
     *
     * Injects In-Reply-To and References headers using the original
     * inbound email's Message-ID so that the outgoing reply groups
     * perfectly inside the customer's existing Gmail thread.
     */
    public function headers(): Headers
    {
        $textHeaders = [];

        if ($this->ticket->message_id) {
            $cleanOriginalId = trim($this->ticket->message_id, " \t\n\r\0\x0B<>");
            $textHeaders['In-Reply-To'] = '<' . $cleanOriginalId . '>';
            $textHeaders['References']  = '<' . $cleanOriginalId . '>';
        }

        return new Headers(
            text: $textHeaders,
        );
    }
}
