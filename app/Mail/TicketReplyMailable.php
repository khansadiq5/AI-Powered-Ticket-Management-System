<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TicketReplyMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Ticket $ticket,
        public TicketReply $reply
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('2203051050509@paruluniversity.ac.in', 'Helpdesk Support'),
            replyTo: [
                new Address('823f29a85a7d6de8df851a12b49d4795@inbound.postmarkapp.com', 'Helpdesk Inbound')
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
     * Get the message headers.
     */
    public function headers(): Headers
    {
        $textHeaders = [];

        if ($this->ticket->message_id) {
            $cleanOriginalId = trim($this->ticket->message_id, " \t\n\r\0\x0B<>");
            $textHeaders['In-Reply-To'] = '<' . $cleanOriginalId . '>';
            $textHeaders['References'] = '<' . $cleanOriginalId . '>';
        }

        return new Headers(
            text: $textHeaders,
        );
    }
}
