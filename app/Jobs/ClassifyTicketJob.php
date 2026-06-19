<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketClassifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Ticket $ticket)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(TicketClassifierService $classifier): void
    {
        $classifier->classify($this->ticket);
    }
}
