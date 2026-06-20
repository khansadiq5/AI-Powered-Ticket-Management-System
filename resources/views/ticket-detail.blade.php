<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->ticket_number }} — {{ $ticket->subject }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.9/purify.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }

        /* Priority badge colors */
        .badge-urgent { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-high   { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .badge-medium { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-low    { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }

        /* Status badge colors */
        .badge-open        { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
        .badge-in_progress { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-resolved    { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
        .badge-closed      { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

        .email-body {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.6;
        }

        .custom-select {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 0.875rem;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border-radius: 0.75rem;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 0.85rem;
        }
        .custom-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-900 bg-slate-50/50 relative overflow-x-hidden">    <!-- Decorative lights -->
    <div class="absolute top-0 right-0 w-[40rem] h-[40rem] rounded-full bg-indigo-200/10 blur-3xl pointer-events-none -z-10"></div>
    <div class="absolute top-1/2 left-[-10rem] w-[40rem] h-[40rem] rounded-full bg-purple-200/10 blur-3xl pointer-events-none -z-10"></div>

    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-200/50 h-16 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40 shadow-xs">
        <!-- Left Side: Brand Logo -->
        <a href="{{ $user->role === 'admin' ? '/admin' : '/agent' }}" class="flex items-center gap-1.5 transition">
            <span class="text-sm tracking-wider text-slate-955 flex items-center gap-1">
                <span class="font-extrabold text-slate-955">TICKET</span>
                <span class="font-light text-slate-400">SYSTEM</span>
            </span>
        </a>
        
        <!-- Center: Desktop Navigation Links -->
        <div class="hidden md:flex items-center gap-1 h-full">
            @if($user->role === 'admin')
                <a href="/admin" class="{{ Request::is('admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Dashboard</a>
                <a href="/admin/users" class="{{ Request::is('admin/users*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Users</a>
                <a href="/admin/tickets" class="{{ Request::is('admin/tickets*') || (Request::is('agent/tickets*') && $user->role === 'admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Tickets</a>
            @endif
        </div>

        <!-- Right Side: User Profile and Sign Out -->
        <div class="hidden md:flex items-center gap-6" id="nav-actions-desktop">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center font-bold text-sm text-slate-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex flex-col">
                    <span class="font-semibold text-xs text-slate-800 leading-none">{{ $user->name }}</span>
                    <span class="text-[10px] font-bold text-indigo-650 uppercase tracking-wider mt-0.5">{{ $user->role }}</span>
                </div>
            </div>
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-750 font-bold rounded-xl py-2 px-4 text-xs transition cursor-pointer">Sign Out</button>
            </form>
        </div>

        <!-- Mobile Menu Hamburger Button -->
        <button id="mobile-menu-btn" class="block md:hidden p-2 rounded-xl text-slate-500 hover:text-slate-855 hover:bg-slate-55 cursor-pointer focus:outline-hidden transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <!-- Mobile Sidebar Drawer -->
    <div id="mobile-sidebar" class="fixed top-0 left-0 bottom-0 w-72 bg-white border-r border-slate-200/80 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out p-6 flex flex-col gap-8 shadow-2xl">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
            <div class="flex items-center gap-1.5">
                <span class="font-extrabold text-base text-slate-955 tracking-tight">TICKET <span class="font-light text-slate-400">SYSTEM</span></span>
            </div>
            <button id="mobile-sidebar-close" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-55 cursor-pointer focus:outline-hidden transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 flex flex-col justify-between" id="nav-actions-mobile">
            <div class="flex flex-col gap-6">
                <!-- User Info -->
                <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                    <div class="w-10 h-10 rounded-xl bg-slate-200 flex items-center justify-center font-bold text-slate-705">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-sm text-slate-800 truncate leading-tight">{{ $user->name }}</span>
                        <span class="text-xs text-slate-555 truncate mt-0.5">{{ $user->email }}</span>
                    </div>
                </div>
                
                <!-- Navigation -->
                @if($user->role === 'admin')
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 mb-2">Navigation</span>
                    <a href="/admin" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Dashboard</a>
                    <a href="/admin/users" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Manage Users</a>
                    <a href="/admin/tickets" class="bg-slate-900 text-white font-semibold shadow-xs text-sm py-2.5 px-3 rounded-xl transition">Tickets</a>
                </div>
                @endif
            </div>
            
            <!-- Sign out -->
            <form method="POST" action="/logout" class="m-0 border-t border-slate-100 pt-4">
                @csrf
                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-705 font-bold rounded-xl py-3 px-4 text-sm transition cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign Out
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 py-8 px-4 md:px-8 max-w-7xl mx-auto w-full relative z-10">

        <!-- Flash Message -->
        @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200/60 text-emerald-800 px-4 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-550 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Back Link -->
        <div class="mb-6">
            <a href="{{ $user->role === 'admin' ? '/admin/tickets' : '/agent' }}" class="text-[10px] text-slate-400 hover:text-slate-800 font-bold uppercase tracking-widest inline-flex items-center gap-1.5 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Tickets
            </a>
        </div>

        <!-- Ticket Header -->
        <div class="mb-8 border-b border-slate-200/60 pb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="font-mono text-[9px] text-slate-800 font-bold bg-slate-100 border border-slate-205 px-2.5 py-0.5 rounded-lg shadow-3xs">{{ $ticket->ticket_number }}</span>
                    <span class="badge-{{ $ticket->priority }} text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider">{{ $ticket->priority }}</span>
                    <span id="ticket-status-badge" class="badge-{{ $ticket->status }} text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider">{{ str_replace('_', ' ', $ticket->status) }}</span>
                    <span id="ticket-category-badge" class="bg-indigo-50/50 text-indigo-700 border border-indigo-100 text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider">{{ $ticket->category ?? 'General' }}</span>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-950 tracking-tight leading-snug">{{ $ticket->subject }}</h1>
            </div>
        </div>

        <!-- Main Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left Column: Thread and forms -->
            <div class="lg:col-span-2 space-y-6">
                <!-- AI Copilot Card -->
                <div id="ai-summary-card" class="bg-white/95 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs relative overflow-hidden group">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-indigo-50 rounded-full blur-xl group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="flex items-center gap-2 mb-3 relative z-10">
                        <span class="text-lg">🤖</span>
                        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">AI Summary</h3>
                    </div>
                    <p id="ai-summary-text" class="text-xs text-slate-705 leading-relaxed mb-4 relative z-10 font-semibold">
                        {{ $ticket->ai_summary ?? 'No summary generated yet. Click "Summarize" below to analyze the ticket and conversation history.' }}
                    </p>
                    <button type="button" id="summarize-btn"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 text-slate-800 bg-slate-50 hover:bg-slate-100 font-bold text-xs transition cursor-pointer relative z-10">
                        Summarize Conversation
                    </button>
                </div>

                <!-- Combined Ticket Conversation Thread -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-xs overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-200/60 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Conversation Feed</h3>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest" id="reply-count">{{ count($ticket->replies) + 1 }} messages</span>
                    </div>

                    <!-- Scrollable timeline container -->
                    <div id="replies-scroll-container" style="max-height: 450px; overflow-y: auto;" class="p-6 space-y-6">
                        
                        <!-- Customer Original Message -->
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-950 text-white flex items-center justify-center font-extrabold text-sm flex-shrink-0">
                                {{ strtoupper(substr($ticket->sender_name ?? $ticket->sender_email, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-slate-50/60 border border-slate-200/50 rounded-2xl p-5 shadow-3xs">
                                    <div class="flex items-center justify-between gap-2 flex-wrap mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-900 leading-none">{{ $ticket->sender_name ?? 'Sender' }}</span>
                                            <span class="bg-slate-200 text-slate-700 text-[8px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Customer</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-semibold" title="{{ $ticket->created_at }}">{{ $ticket->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="email-body text-xs text-slate-800 leading-relaxed select-text font-medium">{{ $ticket->body }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Replies Loop -->
                        <div class="space-y-6" id="replies-container">
                            <div id="no-replies-message" class="{{ $ticket->replies->isEmpty() ? '' : 'hidden' }} text-center py-6 text-slate-400 text-xs font-semibold">
                                No replies posted yet. Use the form below to start the conversation.
                            </div>
                            @foreach($ticket->replies as $reply)
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center font-bold text-sm text-slate-700 flex-shrink-0">
                                        {{ strtoupper(substr($reply->user ? $reply->user->name : ($reply->message_type === 'incoming' ? ($ticket->sender_name ?? $ticket->sender_email) : 'Guest'), 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-3xs">
                                            <div class="flex items-center justify-between gap-2 flex-wrap mb-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-slate-900 leading-none">{{ $reply->user ? $reply->user->name : ($reply->message_type === 'incoming' ? ($ticket->sender_name ?? $ticket->sender_email) : 'Guest') }}</span>
                                                    <span class="text-[8px] px-2 py-0.5 rounded font-bold uppercase tracking-wider {{ ($reply->user && $reply->user->role === 'admin') ? 'bg-slate-950 text-white shadow-3xs' : ((($reply->user && $reply->user->role === 'customer') || $reply->message_type === 'incoming') ? 'bg-slate-200 text-slate-700' : 'bg-emerald-50 text-emerald-800 border border-emerald-100') }}">
                                                        {{ $reply->user ? $reply->user->role : ($reply->message_type === 'incoming' ? 'customer' : 'guest') }}
                                                    </span>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-semibold" title="{{ $reply->created_at }}">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="text-xs text-slate-800 leading-relaxed whitespace-pre-wrap select-text font-medium">{{ $reply->body }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Submit Reply Form -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Post a Reply</h3>
                    <form method="POST" action="/agent/tickets/{{ $ticket->id }}/replies" id="reply-form" class="m-0">
                        @csrf
                        <div class="mb-4">
                            <textarea name="body" id="reply-body" rows="4" required placeholder="Type your response here..." 
                                      class="w-full bg-white border border-slate-200 rounded-xl text-xs text-slate-900 p-4 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 placeholder:text-slate-400 resize-none transition"></textarea>
                        </div>
                        <div class="flex justify-between items-center">
                            <button type="button" id="polish-btn" disabled
                                    class="border border-slate-200 text-slate-400 bg-slate-50 font-bold rounded-xl py-2.5 px-4 text-xs transition cursor-not-allowed opacity-60 flex items-center gap-1.5">
                                Polish Reply
                            </button>
                            <button type="submit" 
                                    class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl py-2.5 px-5 text-xs transition cursor-pointer shadow-md shadow-slate-950/10">
                                Send Response
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <div class="space-y-6 lg:sticky lg:top-24">
                <!-- Unified Ticket Control Panel -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-xs">
                    <div class="px-6 py-4 border-b border-slate-200/60 bg-slate-50/50">
                        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ticket Details</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Sender Details -->
                        <div class="space-y-3">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block">Customer Contact</span>
                            <div class="flex items-center gap-3 bg-slate-50/80 p-3 rounded-xl border border-slate-200/50">
                                <div class="w-10 h-10 rounded-xl bg-slate-200/80 flex items-center justify-center font-bold text-sm text-slate-700 flex-shrink-0">
                                    {{ strtoupper(substr($ticket->sender_name ?? $ticket->sender_email, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-905 truncate leading-none">{{ $ticket->sender_name ?? 'Customer' }}</div>
                                    <div class="text-[10px] text-slate-400 truncate mt-1.5 leading-none" title="{{ $ticket->sender_email }}">{{ $ticket->sender_email }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Actions Form -->
                        <div class="pt-6 border-t border-slate-200/60 space-y-4">
                            <!-- Status Update Form -->
                            <form method="POST" action="/agent/tickets/{{ $ticket->id }}/status" id="status-form" class="m-0 space-y-2">
                                @csrf
                                @method('PATCH')
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block" for="status-select">Status</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select name="status" id="status-select" class="custom-select w-full py-2 text-xs">
                                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl px-4 text-xs transition cursor-pointer whitespace-nowrap">
                                        Save
                                    </button>
                                </div>
                            </form>

                            <!-- Category Update Form -->
                            <form method="POST" action="/agent/tickets/{{ $ticket->id }}/category" id="category-form" class="m-0 space-y-2">
                                @csrf
                                @method('PATCH')
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block" for="category-select">Category</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select name="category" id="category-select" class="custom-select w-full py-2 text-xs">
                                            <option value="General" {{ ($ticket->category ?? 'General') === 'General' ? 'selected' : '' }}>General</option>
                                            <option value="Refund" {{ $ticket->category === 'Refund' ? 'selected' : '' }}>Refund</option>
                                            <option value="Technical" {{ $ticket->category === 'Technical' ? 'selected' : '' }}>Technical</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl px-4 text-xs transition cursor-pointer whitespace-nowrap">
                                        Save
                                    </button>
                                </div>
                            </form>

                            {{-- Assign Agent Form --}}
                            @if($user->role === 'admin' && isset($agents))
                            <form method="POST" action="/tickets/{{ $ticket->id }}/assign" id="assign-form" class="m-0 space-y-2">
                                @csrf
                                @method('PATCH')
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block" for="assigned-to-select">Assign Agent</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select name="assigned_to" id="assigned-to-select" class="custom-select w-full py-2 text-xs">
                                            <option value="">Unassigned</option>
                                            @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl px-4 text-xs transition cursor-pointer whitespace-nowrap">
                                        Assign
                                    </button>
                                </div>
                            </form>
                            @endif
                        </div>

                        <!-- Metadata Details -->
                        <div class="pt-6 border-t border-slate-200/60 space-y-3">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block">Metadata details</span>
                            <div class="space-y-3 bg-slate-50/80 p-4 rounded-xl border border-slate-200/50 text-[11px] font-semibold text-slate-600">
                                <div class="flex justify-between items-center">
                                    <span>Ticket ID</span>
                                    <span class="text-slate-900 font-bold">{{ $ticket->ticket_number }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span>Inbound Channel</span>
                                    <span class="text-slate-900 font-bold capitalize">{{ $ticket->source ?? 'web' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span>Date Created</span>
                                    <span class="text-slate-900 font-bold">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2.5 border-t border-slate-200/60" id="assigned-agent-row">
                                    <span>Current Assignee</span>
                                    <span class="text-slate-950 font-bold" id="assigned-agent-name">{{ $ticket->assignedAgent ? $ticket->assignedAgent->name : 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Helper to show custom toasts
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                if (!container) return;
                
                const toast = document.createElement('div');
                toast.className = `transform translate-y-3 opacity-0 transition-all duration-300 pointer-events-auto px-4 py-3 rounded-xl shadow-lg text-xs font-bold flex items-center gap-2 border ${
                    type === 'success' 
                        ? 'bg-slate-950 text-white border-slate-900' 
                        : 'bg-rose-50 text-rose-800 border-rose-200/60'
                }`;
                
                const icon = type === 'success'
                    ? `<svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
                    : `<svg class="w-4 h-4 text-rose-550 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
                    
                toast.innerHTML = `${icon} <span>${message}</span>`;
                container.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.remove('translate-y-3', 'opacity-0');
                }, 10);
                
                setTimeout(() => {
                    toast.classList.add('translate-y-3', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Helper to send AJAX requests
            async function sendAjaxRequest(form, onSuccess) {
                const url = form.getAttribute('action');
                const formData = new FormData(form);
                const method = 'POST';

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `Saving...`;
                }

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': formData.get('_token')
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showToast(data.message || 'Updated successfully.');
                        onSuccess(data);
                    } else {
                        showToast(data.message || 'An error occurred. Please try again.', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showToast('Network error. Please check your connection.', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            }

            // Intercept Status Form
            const statusForm = document.getElementById('status-form');
            if (statusForm) {
                statusForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(statusForm, (data) => {
                        const badge = document.getElementById('ticket-status-badge');
                        if (badge) {
                            badge.textContent = data.status_label;
                            badge.className = `badge-${data.status} text-[9px] px-2.5 py-0.5 rounded-lg font-bold uppercase tracking-wider`;
                        }
                    });
                });
            }

            // Intercept Category Form
            const categoryForm = document.getElementById('category-form');
            if (categoryForm) {
                categoryForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(categoryForm, (data) => {
                        const badge = document.getElementById('ticket-category-badge');
                        if (badge) {
                            badge.textContent = data.category;
                        }
                    });
                });
            }

            // Intercept agent assign form
            const assignForm = document.getElementById('assign-form');
            if (assignForm) {
                assignForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(assignForm, (data) => {
                        const agentNameEl = document.getElementById('assigned-agent-name');
                        if (agentNameEl) {
                            agentNameEl.textContent = data.assigned_agent_name;
                        }
                    });
                });
            }

            // Intercept Replies Form
            const replyForm = document.getElementById('reply-form');
            if (replyForm) {
                replyForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(replyForm, (data) => {
                        const noRepliesMsg = document.getElementById('no-replies-message');
                        if (noRepliesMsg) {
                            noRepliesMsg.classList.add('hidden');
                        }

                        const repliesContainer = document.getElementById('replies-container');
                        if (repliesContainer) {
                            repliesContainer.classList.remove('hidden');

                            // Create new reply element
                            const replyDiv = document.createElement('div');
                            replyDiv.className = 'flex items-start gap-4 transform scale-95 opacity-0 transition-all duration-300';
                            
                            const cleanInitial = DOMPurify.sanitize(data.reply.user.initial);
                            const cleanName = DOMPurify.sanitize(data.reply.user.name);
                            const cleanRole = DOMPurify.sanitize(data.reply.user.role);
                            const cleanTime = DOMPurify.sanitize(data.reply.created_at_human);
                            const cleanBody = DOMPurify.sanitize(data.reply.body);

                            replyDiv.innerHTML = `
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center font-bold text-sm text-slate-705 flex-shrink-0">
                                    ${cleanInitial}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-3xs">
                                        <div class="flex items-center justify-between gap-2 flex-wrap mb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-900">${cleanName}</span>
                                                <span class="text-[8px] px-2 py-0.5 rounded font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                                    ${cleanRole}
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-semibold">${cleanTime}</span>
                                        </div>
                                        <div class="text-xs text-slate-805 leading-relaxed whitespace-pre-wrap select-text font-medium">${cleanBody}</div>
                                    </div>
                                </div>
                            `;

                            repliesContainer.appendChild(replyDiv);
                            
                            setTimeout(() => {
                                replyDiv.classList.remove('scale-95', 'opacity-0');
                            }, 50);

                            // Increment message count
                            const replyCountEl = document.getElementById('reply-count');
                            if (replyCountEl) {
                                const matches = replyCountEl.textContent.match(/\d+/);
                                if (matches) {
                                    const count = parseInt(matches[0], 10) + 1;
                                    replyCountEl.textContent = `${count} messages`;
                                }
                            }

                            // Scroll to bottom
                            const scrollContainer = document.getElementById('replies-scroll-container');
                            if (scrollContainer) {
                                setTimeout(() => {
                                    scrollContainer.scrollTop = scrollContainer.scrollHeight;
                                }, 100);
                            }
                        }

                        // Reset body text
                        const replyBody = document.getElementById('reply-body');
                        if (replyBody) {
                            replyBody.value = '';
                            updatePolishBtnState();
                        }
                    });
                });
            }

            // Polish Reply Functionality
            const polishBtn = document.getElementById('polish-btn');
            const replyBodyInput = document.getElementById('reply-body');

            function updatePolishBtnState() {
                if (!polishBtn || !replyBodyInput) return;
                const hasText = replyBodyInput.value.trim().length > 0;
                if (hasText) {
                    polishBtn.disabled = false;
                    polishBtn.className = "border border-slate-200 text-slate-800 bg-slate-50 hover:bg-slate-100 font-bold rounded-xl py-2.5 px-4 text-xs transition cursor-pointer";
                } else {
                    polishBtn.disabled = true;
                    polishBtn.className = "border border-slate-200 text-slate-400 bg-slate-50 font-bold rounded-xl py-2.5 px-4 text-xs transition cursor-not-allowed opacity-60 flex items-center gap-1.5";
                }
            }
            
            if (polishBtn && replyBodyInput) {
                replyBodyInput.addEventListener('input', updatePolishBtnState);
                updatePolishBtnState();

                polishBtn.addEventListener('click', async () => {
                    const text = replyBodyInput.value.trim();
                    if (!text) {
                        showToast('Please type a draft response first before polishing.', 'error');
                        return;
                    }
                    
                    const originalBtnText = polishBtn.innerHTML;
                    polishBtn.disabled = true;
                    polishBtn.innerHTML = `Polishing...`;
                    
                    try {
                        const csrfToken = document.querySelector('#reply-form input[name="_token"]').value;
                        const response = await fetch(`/agent/tickets/{{ $ticket->id }}/polish-reply`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ body: text })
                        });
                        
                        const data = await response.json();
                        if (response.ok && data.success) {
                            replyBodyInput.value = data.polished;
                            showToast('Reply polished successfully!');
                        } else {
                            showToast(data.message || 'An error occurred while polishing.', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        showToast('Network error. Please check your connection.', 'error');
                    } finally {
                        polishBtn.disabled = false;
                        polishBtn.innerHTML = originalBtnText;
                        updatePolishBtnState();
                    }
                });
            }

            // Summarize Ticket Functionality
            const summarizeBtn = document.getElementById('summarize-btn');
            const aiSummaryText = document.getElementById('ai-summary-text');
            
            if (summarizeBtn && aiSummaryText) {
                summarizeBtn.addEventListener('click', async () => {
                    const originalBtnText = summarizeBtn.innerHTML;
                    summarizeBtn.disabled = true;
                    summarizeBtn.innerHTML = `Summarizing...`;
                    
                    try {
                        const csrfToken = document.querySelector('#reply-form input[name="_token"]').value;
                        const response = await fetch(`/tickets/{{ $ticket->id }}/summarize`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });
                        
                        const data = await response.json();
                        if (response.ok && data.success) {
                            aiSummaryText.textContent = data.summary;
                            showToast('Summary re-generated successfully!');
                        } else {
                            showToast(data.message || 'An error occurred while generating summary.', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        showToast('Network error. Please check your connection.', 'error');
                    } finally {
                        summarizeBtn.disabled = false;
                        summarizeBtn.innerHTML = originalBtnText;
                    }
                });
            }

            // Scroll replies feed to bottom on load
            const initialScrollContainer = document.getElementById('replies-scroll-container');
            if (initialScrollContainer) {
                initialScrollContainer.scrollTop = initialScrollContainer.scrollHeight;
            }

            // Mobile Sidebar Control
            function openMobileSidebar() {
                const sidebar = document.getElementById("mobile-sidebar");
                const backdrop = document.getElementById("mobile-sidebar-backdrop");
                sidebar.classList.remove("-translate-x-full");
                sidebar.classList.add("translate-x-0");
                backdrop.classList.remove("opacity-0", "pointer-events-none");
                backdrop.classList.add("opacity-100");
            }

            document.getElementById("mobile-menu-btn").addEventListener("click", openMobileSidebar);
            document.getElementById("mobile-sidebar-close").addEventListener("click", () => {
                const sidebar = document.getElementById("mobile-sidebar");
                const backdrop = document.getElementById("mobile-sidebar-backdrop");
                sidebar.classList.remove("translate-x-0");
                sidebar.classList.add("-translate-x-full");
                backdrop.classList.remove("opacity-100");
                backdrop.classList.add("opacity-0", "pointer-events-none");
            });
            document.getElementById("mobile-sidebar-backdrop").addEventListener("click", () => {
                const sidebar = document.getElementById("mobile-sidebar");
                const backdrop = document.getElementById("mobile-sidebar-backdrop");
                sidebar.classList.remove("translate-x-0");
                sidebar.classList.add("-translate-x-full");
                backdrop.classList.remove("opacity-100");
                backdrop.classList.add("opacity-0", "pointer-events-none");
            });
        });
    </script>
</body>
</html>
