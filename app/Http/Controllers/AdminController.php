<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Display the Admin panel dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        $ticketStats = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'unassigned' => Ticket::unassigned()->count(),
        ];

        // AI Performance Analytics
        $resolvedByAiCount = Ticket::where('status', 'resolved')
            ->whereHas('replies', function($query) {
                $query->whereHas('user', function($q) {
                    $q->where('email', 'ai.assistant@helpdesk.com');
                });
            })->count();

        $totalResolved = Ticket::where('status', 'resolved')->count();
        $percentageSolvedByAi = $totalResolved > 0 ? round(($resolvedByAiCount / $totalResolved) * 100, 1) : 0;

        // Average Resolution Time
        $resolvedTickets = Ticket::where('status', 'resolved')->get(['created_at', 'updated_at']);
        $totalSeconds = 0;
        $resolvedCount = $resolvedTickets->count();
        foreach ($resolvedTickets as $t) {
            $totalSeconds += abs($t->updated_at->timestamp - $t->created_at->timestamp);
        }
        $averageResolutionSeconds = $resolvedCount > 0 ? $totalSeconds / $resolvedCount : 0;

        if ($averageResolutionSeconds == 0) {
            $averageResolutionTimeFormatted = 'N/A';
        } elseif ($averageResolutionSeconds < 60) {
            $secs = round($averageResolutionSeconds);
            $averageResolutionTimeFormatted = "{$secs} secs";
        } else {
            $minutes = round($averageResolutionSeconds / 60);
            if ($minutes < 60) {
                $averageResolutionTimeFormatted = "{$minutes} mins";
            } else {
                $hours = floor($minutes / 60);
                $remainingMinutes = $minutes % 60;
                $averageResolutionTimeFormatted = "{$hours}h {$remainingMinutes}m";
            }
        }

        // Charts data
        $trends = Ticket::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trendLabels = [];
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $formattedDate = now()->subDays($i)->format('D, M d');
            $trendLabels[] = $formattedDate;
            $match = $trends->firstWhere('date', $date);
            $trendData[] = $match ? $match->count : 0;
        }

        $statusCounts = [
            'open' => $ticketStats['open'],
            'in_progress' => $ticketStats['in_progress'],
            'resolved' => $ticketStats['resolved'],
            'closed' => Ticket::where('status', 'closed')->count(),
        ];

        return view('admin', [
            'user' => $user,
            'ticketStats' => $ticketStats,
            'resolvedByAiCount' => $resolvedByAiCount,
            'percentageSolvedByAi' => $percentageSolvedByAi,
            'averageResolutionTime' => $averageResolutionTimeFormatted,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Display user management list.
     */
    public function users()
    {
        return view('admin-users', [
            'user' => Auth::user(),
            'users' => User::orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Create a new user.
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:admin,agent'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User created successfully.');
    }

    /**
     * Update an existing user.
     */
    public function updateUser(Request $request, User $user)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,agent'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    /**
     * Display all tickets with filters.
     */
    public function tickets(Request $request)
    {
        $query = Ticket::with('assignedAgent')->whereNotIn('status', ['new', 'processing']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->unassigned();
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('sender_email', 'like', "%{$search}%");
            });
        }

        $tickets = $query
            ->orderByRaw("CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END ASC")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $agents = User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get();

        return view('admin-tickets', [
            'user' => Auth::user(),
            'tickets' => $tickets,
            'agents' => $agents,
            'filters' => $request->only(['status', 'priority', 'assigned_to', 'search', 'category']),
        ]);
    }

    /**
     * Display a single ticket detail for Admin.
     */
    public function showTicket(Ticket $ticket)
    {
        $user = Auth::user();
        $agents = User::whereIn('role', ['agent', 'admin'])->orderBy('name')->get();
        $ticket->load('replies.user');

        return view('ticket-detail', compact('user', 'ticket', 'agents'));
    }

    /**
     * Assign a ticket to an agent.
     */
    public function assignTicket(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->update(['assigned_to' => $validated['assigned_to']]);

        $agentName = $validated['assigned_to']
            ? User::find($validated['assigned_to'])->name
            : 'Unassigned';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'assigned_agent_name' => $agentName,
                'message' => "Ticket assigned to {$agentName}.",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} assigned to {$agentName}.");
    }
}
