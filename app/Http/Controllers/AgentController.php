<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    /**
     * Display the Agent panel dashboard with assigned tickets.
     */
    public function index()
    {
        $user = Auth::user();

        $tickets = Ticket::assignedTo($user->id)
            ->orderByRaw("CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END ASC")
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $tickets->count(),
            'open' => $tickets->where('status', 'open')->count(),
            'in_progress' => $tickets->where('status', 'in_progress')->count(),
            'resolved' => $tickets->where('status', 'resolved')->count(),
        ];

        return view('agent', compact('user', 'tickets', 'stats'));
    }

    /**
     * Display a single ticket detail.
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        // Agents can only view their own tickets
        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to view this ticket.');
        }

        $ticket->load('replies.user');

        return view('ticket-detail', compact('user', 'ticket'));
    }

    /**
     * Update the status of a ticket.
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to update this ticket.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update(['status' => $validated['status']]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $ticket->status,
                'status_label' => str_replace('_', ' ', $ticket->status),
                'message' => "Ticket status updated to " . str_replace('_', ' ', $ticket->status) . ".",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} status updated to {$validated['status']}.");
    }

    /**
     * Update the category of a ticket.
     */
    public function updateCategory(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to update this ticket.');
        }

        $validated = $request->validate([
            'category' => ['required', 'in:General,Refund,Technical'],
        ]);

        $ticket->update(['category' => $validated['category']]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'category' => $ticket->category,
                'message' => "Ticket category updated to {$ticket->category}.",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} category updated to {$validated['category']}.");
    }

    /**
     * Store a new reply for a ticket.
     */
    public function storeReply(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->assigned_to !== $user->id && $user->role !== 'admin') {
            abort(403, 'You are not authorized to reply to this ticket.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1'],
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully.',
                'reply' => [
                    'id' => $reply->id,
                    'body' => $reply->body,
                    'created_at_human' => $reply->created_at->diffForHumans(),
                    'user' => [
                        'name' => $user->name,
                        'role' => $user->role,
                        'initial' => strtoupper(substr($user->name, 0, 1)),
                    ]
                ]
            ]);
        }

        return back()->with('success', 'Reply posted successfully.');
    }
}
