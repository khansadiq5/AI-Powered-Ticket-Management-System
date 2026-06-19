<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->ticket_number }} — {{ $ticket->subject }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            line-height: 1.7;
        }

        /* AI summary container */
        .ai-card {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-900 bg-slate-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200/80 h-16 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40">
        <!-- Left Side: Brand Logo -->
        <a href="{{ $user->role === 'admin' ? '/admin' : '/agent' }}" class="text-lg tracking-wider text-slate-900 flex-shrink-0 flex items-center gap-1.5">
            <span class="font-extrabold tracking-tight text-slate-950">TICKET</span>
            <span class="font-light text-slate-400">SYSTEM</span>
        </a>
        
        <!-- Center: Desktop Navigation Links -->
        <div class="hidden md:flex items-center gap-2 h-full">
            @if($user->role === 'admin')
                <a href="/admin" class="{{ Request::is('admin') ? 'bg-slate-900 text-white shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }} text-sm px-4 py-2 rounded-lg transition duration-200">Dashboard</a>
                <a href="/admin/users" class="{{ Request::is('admin/users*') ? 'bg-slate-900 text-white shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }} text-sm px-4 py-2 rounded-lg transition duration-200">Users</a>
                <a href="/admin/tickets" class="{{ Request::is('admin/tickets*') || (Request::is('agent/tickets*') && $user->role === 'admin') ? 'bg-slate-900 text-white shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }} text-sm px-4 py-2 rounded-lg transition duration-200">Tickets</a>
            @else
                <a href="/agent" class="bg-slate-900 text-white shadow-xs font-semibold text-sm px-4 py-2 rounded-lg transition duration-200">My Tickets</a>
            @endif
        </div>

        <!-- Right Side: User Profile and Sign Out -->
        <div class="hidden md:flex items-center gap-6" id="nav-actions-desktop">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="font-medium text-sm text-slate-700">{{ $user->name }}</span>
                <span class="{{ $user->role === 'admin' ? 'bg-slate-100 text-slate-800 border-slate-200/50' : 'bg-emerald-50 text-emerald-700 border-emerald-200/40' }} text-xs px-2.5 py-0.5 rounded-full border font-semibold uppercase tracking-wider">{{ $user->role }}</span>
            </div>
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-1.5 px-4 text-xs transition cursor-pointer">Sign Out</button>
            </form>
        </div>

        <!-- Mobile Menu Hamburger Button -->
        <button id="mobile-menu-btn" class="block md:hidden p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-50 focus:outline-none cursor-pointer transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <!-- Mobile Sidebar Drawer -->
    <div id="mobile-sidebar" class="fixed top-0 left-0 bottom-0 w-72 bg-white border-r border-slate-200 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out p-6 flex flex-col gap-8 shadow-2xl">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
            <div class="flex items-center gap-1.5">
                <span class="font-extrabold text-base text-slate-950 tracking-tight">TICKET</span>
                <span class="font-light text-base text-slate-400">SYSTEM</span>
            </div>
            <button id="mobile-sidebar-close" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 cursor-pointer focus:outline-none transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 flex flex-col justify-between" id="nav-actions-mobile">
            <div class="flex flex-col gap-6">
                <!-- User Info -->
                <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-700">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-sm text-slate-800 truncate">{{ $user->name }}</span>
                        <span class="text-xs text-slate-500 truncate mt-0.5">{{ $user->email }}</span>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Navigation</span>
                    @if($user->role === 'admin')
                        <a href="/admin" class="{{ Request::is('admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }} text-sm py-2.5 px-3 rounded-lg transition">Dashboard</a>
                        <a href="/admin/users" class="{{ Request::is('admin/users*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }} text-sm py-2.5 px-3 rounded-lg transition">Manage Users</a>
                        <a href="/admin/tickets" class="{{ Request::is('admin/tickets*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium' }} text-sm py-2.5 px-3 rounded-lg transition">Tickets</a>
                    @else
                        <a href="/agent" class="bg-slate-900 text-white font-semibold shadow-xs text-sm py-2.5 px-3 rounded-lg transition">My Tickets</a>
                    @endif
                </div>
            </div>
            
            <!-- Sign out -->
            <form method="POST" action="/logout" class="m-0 border-t border-slate-100 pt-4">
                @csrf
                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-2.5 px-4 text-sm transition cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign Out
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 py-8 px-4 md:px-8 max-w-6xl mx-auto w-full">

        <!-- Flash Message -->
        @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2 shadow-xs">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Breadcrumbs -->
        <div class="mb-6">
            <a href="{{ $user->role === 'admin' ? '/admin/tickets' : '/agent' }}" class="text-slate-500 hover:text-slate-800 text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Tickets
            </a>
        </div>

        <!-- Ticket Header -->
        <div class="mb-8">
            <div class="flex flex-wrap items-center gap-2.5 mb-3">
                <span class="font-mono text-xs text-slate-800 font-semibold bg-slate-100 px-3 py-1 rounded-lg border border-slate-200/80">{{ $ticket->ticket_number }}</span>
                <span class="badge-{{ $ticket->priority }} text-xs px-2.5 py-0.5 rounded-full border font-semibold uppercase tracking-wider">{{ $ticket->priority }}</span>
                <span id="ticket-status-badge" class="badge-{{ $ticket->status }} text-xs px-2.5 py-0.5 rounded-full border font-semibold capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                <span id="ticket-category-badge" class="bg-indigo-55 text-indigo-700 border border-indigo-100/60 bg-indigo-50/50 text-xs px-2.5 py-0.5 rounded-full font-semibold">{{ $ticket->category ?? 'General' }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 leading-snug tracking-tight">{{ $ticket->subject }}</h1>
        </div>

        <!-- Main Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left Column: Main support ticket & thread -->
            <div class="lg:col-span-2 space-y-6">
                <!-- AI Summary -->
                <div id="ai-summary-card" class="ai-card rounded-2xl p-5 shadow-xs border border-emerald-200/50 bg-emerald-50/40 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-emerald-100/30 rounded-full blur-xl"></div>
                    <div class="flex items-center gap-2 mb-2.5 relative z-10">
                        <span class="text-lg">🤖</span>
                        <h3 class="text-xs font-bold text-emerald-800 uppercase tracking-wider">AI Copilot Summary</h3>
                    </div>
                    <p id="ai-summary-text" class="text-sm text-emerald-950 leading-relaxed relative z-10 mb-3">
                        {{ $ticket->ai_summary ?? 'No summary generated yet. Click "Summarize" below to analyze the ticket and conversation history.' }}
                    </p>
                    <div class="relative z-10 flex justify-start">
                        <button type="button" id="summarize-btn"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-emerald-200 text-emerald-800 bg-emerald-50/80 hover:bg-emerald-100/80 font-bold text-xs transition cursor-pointer shadow-3xs active:scale-95 duration-200">
                            <span>✨</span> Summarize
                        </button>
                    </div>
                </div>

                <!-- Combined Ticket Conversation Thread -->
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Conversation Feed</h3>
                        <span class="text-xs text-slate-400 font-semibold" id="reply-count">{{ count($ticket->replies) + 1 }} messages</span>
                    </div>

                    <!-- Scrollable container -->
                    <div id="replies-scroll-container" class="p-6 overflow-y-auto max-h-[500px] space-y-6 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-200 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-slate-300">
                        
                        <!-- Customer Original Message -->
                        <div class="flex items-start gap-4">
                            <!-- Avatar -->
                            <div class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-sm flex-shrink-0 shadow-xs">
                                {{ strtoupper(substr($ticket->sender_name ?? $ticket->sender_email, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="bg-slate-50/80 rounded-2xl rounded-tl-none border border-slate-200/60 p-4 shadow-3xs">
                                    <div class="flex items-center justify-between gap-2 flex-wrap mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-bold text-slate-900">{{ $ticket->sender_name ?? 'Sender' }}</span>
                                            <span class="bg-slate-200/65 text-slate-700 text-[9px] px-1.5 py-0.5 rounded-full font-extrabold uppercase tracking-wide">Customer</span>
                                        </div>
                                        <span class="text-xs text-slate-400 font-medium" title="{{ $ticket->created_at }}">{{ $ticket->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="email-body text-sm text-slate-800 leading-relaxed whitespace-pre-wrap select-text">{{ $ticket->body }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Replies Loop -->
                        <div class="space-y-6" id="replies-container">
                            <div id="no-replies-message" class="{{ $ticket->replies->isEmpty() ? '' : 'hidden' }} text-center py-4 text-slate-400 text-sm font-medium">
                                No replies posted yet. Use the form below to start the conversation.
                            </div>
                            @foreach($ticket->replies as $reply)
                                <div class="flex items-start gap-4">
                                    <!-- Avatar -->
                                    <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700 flex-shrink-0 shadow-3xs">
                                        {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="bg-white rounded-2xl rounded-tl-none border border-slate-200/60 p-4 shadow-3xs hover:shadow-2xs transition duration-200">
                                            <div class="flex items-center justify-between gap-2 flex-wrap mb-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-sm font-bold text-slate-900">{{ $reply->user->name }}</span>
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full font-extrabold uppercase tracking-wide {{ $reply->user->role === 'admin' ? 'bg-rose-50 text-rose-600 border border-rose-100' : ($reply->user->role === 'customer' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-blue-50 text-blue-600 border border-blue-100') }}">
                                                        {{ $reply->user->role }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-slate-400 font-medium" title="{{ $reply->created_at }}">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap select-text">{{ $reply->body }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Submit Reply Form (Fixed below the thread) -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Post a Reply</h3>
                    <form method="POST" action="/agent/tickets/{{ $ticket->id }}/replies" id="reply-form" class="m-0">
                        @csrf
                        <div class="mb-4">
                            <textarea name="body" id="reply-body" rows="4" required placeholder="Type your response here..." 
                                      class="w-full bg-slate-50/50 border border-slate-200 rounded-xl text-sm text-slate-900 p-4 focus:outline-none focus:ring-2 focus:ring-slate-950/10 focus:border-slate-800 placeholder:text-slate-400 resize-none transition duration-200"></textarea>
                        </div>
                        <div class="flex justify-between items-center">
                            <button type="button" id="polish-btn" disabled
                                    class="border border-slate-200 text-slate-400 bg-slate-50 font-semibold rounded-xl py-3 px-6 text-sm transition cursor-not-allowed shadow-3xs opacity-60 flex items-center gap-2">
                                <span>✨</span> Polish Reply
                            </button>
                            <button type="submit" 
                                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl py-3 px-6 text-sm transition cursor-pointer shadow-xs hover:shadow-md active:scale-98 transition duration-200">
                                Send Response
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Sticky Sidebar -->
            <div class="space-y-6 lg:sticky lg:top-20">
                <!-- Unified Ticket Control Panel -->
                <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ticket Info & Actions</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Sender Details -->
                        <div class="space-y-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Customer Contact</span>
                            <div class="flex items-center gap-3 bg-slate-50/50 p-3 rounded-xl border border-slate-200/60">
                                <div class="w-10 h-10 rounded-full bg-slate-200/80 flex items-center justify-center font-bold text-slate-700 flex-shrink-0 shadow-3xs">
                                    {{ strtoupper(substr($ticket->sender_name ?? $ticket->sender_email, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-900 truncate">{{ $ticket->sender_name ?? 'Customer' }}</div>
                                    <div class="text-xs text-slate-500 truncate mt-0.5" title="{{ $ticket->sender_email }}">{{ $ticket->sender_email }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Actions Form -->
                        <div class="pt-6 border-t border-slate-100 space-y-5">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Manage Ticket</span>
                            
                            <!-- Status Update Form -->
                            <form method="POST" action="/agent/tickets/{{ $ticket->id }}/status" id="status-form" class="m-0 space-y-2">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs font-semibold text-slate-500" for="status-select">Update Status</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select name="status" id="status-select"
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 appearance-none cursor-pointer pr-10">
                                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <button type="submit"
                                            class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl px-4 text-xs transition cursor-pointer flex items-center justify-center">
                                        Save
                                    </button>
                                </div>
                            </form>

                            <!-- Category Update Form -->
                            <form method="POST" action="/agent/tickets/{{ $ticket->id }}/category" id="category-form" class="m-0 space-y-2 pt-1">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs font-semibold text-slate-500" for="category-select">Update Category</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select name="category" id="category-select"
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 appearance-none cursor-pointer pr-10">
                                            <option value="General" {{ ($ticket->category ?? 'General') === 'General' ? 'selected' : '' }}>General</option>
                                            <option value="Refund" {{ $ticket->category === 'Refund' ? 'selected' : '' }}>Refund</option>
                                            <option value="Technical" {{ $ticket->category === 'Technical' ? 'selected' : '' }}>Technical</option>
                                            <option value="Auth" {{ $ticket->category === 'Auth' ? 'selected' : '' }}>Auth</option>
                                            <option value="Billing" {{ $ticket->category === 'Billing' ? 'selected' : '' }}>Billing</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <button type="submit"
                                            class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl px-4 text-xs transition cursor-pointer flex items-center justify-center">
                                        Save
                                    </button>
                                </div>
                            </form>

                            {{-- Assign Agent Form (Admin Only) --}}
                            @if($user->role === 'admin' && isset($agents))
                            <form method="POST" action="/admin/tickets/{{ $ticket->id }}/assign" id="assign-form" class="m-0 space-y-2 pt-1">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs font-semibold text-slate-500" for="assigned-to-select">Assign Agent</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <select name="assigned_to" id="assigned-to-select"
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 appearance-none cursor-pointer pr-10">
                                            <option value="">Unassigned</option>
                                            @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <button type="submit"
                                            class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl px-4 text-xs transition cursor-pointer flex items-center justify-center whitespace-nowrap">
                                        Assign Agent
                                    </button>
                                </div>
                            </form>
                            @endif
                        </div>

                        <!-- Metadata Details -->
                        <div class="pt-6 border-t border-slate-100 space-y-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Ticket Metadata</span>
                            <div class="space-y-3 bg-slate-50/50 p-4 rounded-xl border border-slate-200/60 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Ticket ID</span>
                                    <span class="text-slate-800 font-semibold">{{ $ticket->ticket_number }} (ID: {{ $ticket->id }})</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Sender Email</span>
                                    <span class="text-slate-800 font-semibold truncate max-w-[150px]" title="{{ $ticket->sender_email }}">{{ $ticket->sender_email }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Subject</span>
                                    <span class="text-slate-800 font-semibold truncate max-w-[150px]" title="{{ $ticket->subject }}">{{ $ticket->subject }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Source</span>
                                    <span class="text-slate-800 font-semibold capitalize bg-slate-100 px-2 py-0.5 rounded border border-slate-200/60">{{ $ticket->source ?? 'web' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Status</span>
                                    <span class="text-slate-800 font-semibold capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Created At</span>
                                    <span class="text-slate-800 font-semibold">{{ $ticket->created_at->format('M d, Y · H:i') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Last Reply At</span>
                                    <span class="text-slate-800 font-semibold">{{ $ticket->last_reply_at }}</span>
                                </div>
                                <div class="flex justify-between items-center pt-2.5 border-t border-slate-200/60" id="assigned-agent-row">
                                    <span class="text-slate-500">Current Agent</span>
                                    <span class="text-slate-800 font-bold" id="assigned-agent-name">{{ $ticket->assignedAgent ? $ticket->assignedAgent->name : 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Helper to show custom premium toasts
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                if (!container) return;
                
                const toast = document.createElement('div');
                toast.className = `transform translate-y-2 opacity-0 transition-all duration-300 pointer-events-auto px-4 py-3 rounded-lg shadow-lg text-sm font-semibold flex items-center gap-2 border ${
                    type === 'success' 
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200' 
                        : 'bg-rose-50 text-rose-800 border-rose-200'
                }`;
                
                const icon = type === 'success'
                    ? `<svg class="w-4.5 h-4.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
                    : `<svg class="w-4.5 h-4.5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
                    
                toast.innerHTML = `${icon} <span>${message}</span>`;
                container.appendChild(toast);
                
                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-y-2', 'opacity-0');
                }, 10);
                
                // Animate out and remove
                setTimeout(() => {
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Helper to send AJAX requests
            async function sendAjaxRequest(form, onSuccess) {
                const url = form.getAttribute('action');
                const formData = new FormData(form);
                
                // Always use POST for FormData requests so PHP parses the body correctly.
                // Laravel routes PATCH/PUT endpoints using the hidden _method field.
                const method = 'POST';

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    // Mini spinner
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> Saving...`;
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
                            badge.className = `badge-${data.status} text-xs px-2.5 py-0.5 rounded border font-semibold capitalize`;
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

                            const roleBadgeClass = cleanRole === 'admin' 
                                ? 'bg-rose-50 text-rose-600 border border-rose-100' 
                                : 'bg-blue-50 text-blue-600 border border-blue-100';

                            replyDiv.innerHTML = `
                                <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700 flex-shrink-0 shadow-3xs">
                                    ${cleanInitial}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-white rounded-2xl rounded-tl-none border border-slate-200/60 p-4 shadow-3xs hover:shadow-2xs transition duration-200">
                                        <div class="flex items-center justify-between gap-2 flex-wrap mb-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-sm font-bold text-slate-900">${cleanName}</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded-full font-extrabold uppercase tracking-wide ${roleBadgeClass}">
                                                    ${cleanRole}
                                                </span>
                                            </div>
                                            <span class="text-xs text-slate-400 font-medium">${cleanTime}</span>
                                        </div>
                                        <div class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap select-text">${cleanBody}</div>
                                    </div>
                                </div>
                            `;

                            repliesContainer.appendChild(replyDiv);
                            
                            // Simple animation
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
                    polishBtn.className = "border border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 font-semibold rounded-xl py-3 px-6 text-sm transition cursor-pointer shadow-3xs hover:shadow-2xs active:scale-98 transition duration-200 flex items-center gap-2";
                } else {
                    polishBtn.disabled = true;
                    polishBtn.className = "border border-slate-200 text-slate-400 bg-slate-50 font-semibold rounded-xl py-3 px-6 text-sm transition cursor-not-allowed shadow-3xs opacity-60 flex items-center gap-2";
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
                    polishBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-700 inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> Polishing...`;
                    
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
                    summarizeBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-emerald-800 inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> Summarizing...`;
                    
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
                if (sidebar && backdrop) {
                    sidebar.classList.remove("-translate-x-full");
                    sidebar.classList.add("translate-x-0");
                    backdrop.classList.remove("opacity-0", "pointer-events-none");
                    backdrop.classList.add("opacity-100");
                }
            }

            function closeMobileSidebar() {
                const sidebar = document.getElementById("mobile-sidebar");
                const backdrop = document.getElementById("mobile-sidebar-backdrop");
                if (sidebar && backdrop) {
                    sidebar.classList.remove("translate-x-0");
                    sidebar.classList.add("-translate-x-full");
                    backdrop.classList.remove("opacity-100");
                    backdrop.classList.add("opacity-0", "pointer-events-none");
                }
            }

            const mobileBtn = document.getElementById("mobile-menu-btn");
            if (mobileBtn) mobileBtn.addEventListener("click", openMobileSidebar);

            const mobileCloseBtn = document.getElementById("mobile-sidebar-close");
            if (mobileCloseBtn) mobileCloseBtn.addEventListener("click", closeMobileSidebar);

            const mobileBackdrop = document.getElementById("mobile-sidebar-backdrop");
            if (mobileBackdrop) mobileBackdrop.addEventListener("click", closeMobileSidebar);
        });
    </script>
</body>
</html>
