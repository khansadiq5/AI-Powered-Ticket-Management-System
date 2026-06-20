<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — My Tickets</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
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

        .ticket-row {
            position: relative;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .ticket-row:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }
        .ticket-row-link::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 10;
        }
        .custom-select {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-size: 0.75rem;
            padding: 0.375rem 1.75rem 0.375rem 0.625rem;
            border-radius: 0.75rem;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.625rem center;
            background-size: 0.75rem;
        }
        .custom-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-900 bg-slate-50/50 relative overflow-x-hidden">

    <!-- Decorative lights -->
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
                <a href="/admin" class="text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium text-sm px-4 py-2 rounded-xl transition duration-250">Dashboard</a>
                <a href="/admin/users" class="text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium text-sm px-4 py-2 rounded-xl transition duration-250">Users</a>
                <a href="/admin/tickets" class="text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium text-sm px-4 py-2 rounded-xl transition duration-250">Tickets</a>
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
                        <span class="text-xs text-slate-550 truncate mt-0.5">{{ $user->email }}</span>
                    </div>
                </div>
                
                <!-- Navigation -->
                @if($user->role === 'admin')
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 mb-2">Navigation</span>
                    <a href="/admin" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Dashboard</a>
                    <a href="/admin/users" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Manage Users</a>
                    <a href="/admin/tickets" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Tickets</a>
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

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-955 leading-none">My Assigned Tickets</h1>
            <p class="text-sm text-slate-500 mt-2">Filter and search across all tickets currently assigned to you.</p>
        </div>

        <!-- Filter Card - Single Line layout -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-slate-200/60 p-4 mb-8 shadow-xs">
            <form method="GET" action="/agent/tickets" class="w-full m-0 flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                <!-- Search bar -->
                <div class="relative flex-1 min-w-0 w-full" style="flex-grow: 1; min-width: 300px;">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tickets..." 
                           class="w-full bg-slate-100 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-medium">
                </div>

                <!-- Filters & Action Buttons Row -->
                <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto lg:flex-shrink-0" style="flex-shrink: 0;">
                    <!-- Status -->
                    <select name="status" class="custom-select py-2 px-3 text-xs min-w-[100px]">
                        <option value="">Status</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>

                    <!-- Priority -->
                    <select name="priority" class="custom-select py-2 px-3 text-xs min-w-[100px]">
                        <option value="">Priority</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>

                    <!-- Category -->
                    <select name="category" class="custom-select py-2 px-3 text-xs min-w-[105px]">
                        <option value="">Category</option>
                        <option value="General" {{ request('category') === 'General' ? 'selected' : '' }}>General</option>
                        <option value="Refund" {{ request('category') === 'Refund' ? 'selected' : '' }}>Refund</option>
                        <option value="Technical" {{ request('category') === 'Technical' ? 'selected' : '' }}>Technical</option>
                    </select>

                    <button type="submit" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl py-2 px-4 text-xs transition shadow-sm cursor-pointer ml-auto lg:ml-0">
                        Apply
                    </button>
                    @if(request()->anyFilled(['status', 'priority', 'category', 'search']))
                    <a href="/agent/tickets" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-3.5 text-xs transition">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tickets Table Card -->
        @if($tickets->count() > 0)
        <div class="bg-white/80 backdrop-blur-md border border-slate-200/70 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200/60 text-slate-400 text-[10px] uppercase tracking-widest bg-slate-50/50">
                            <th class="px-6 py-4 font-bold">Subject</th>
                            <th class="px-6 py-4 font-bold hidden md:table-cell">Sender</th>
                            <th class="px-6 py-4 font-bold">Priority</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold hidden lg:table-cell">Created Date & Time</th>
                        </tr>
                    </thead>
                    <!-- Skeleton loader -->
                    <tbody class="divide-y divide-slate-100 bg-white" id="table-skeleton">
                        @for($i = 0; $i < 3; $i++)
                            <tr class="animate-pulse">
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-slate-100 rounded w-48 mb-1.5"></div>
                                    <div class="h-3 bg-slate-100 rounded w-36"></div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="h-4 bg-slate-100 rounded w-28 mb-1.5"></div>
                                    <div class="h-3 bg-slate-100 rounded w-36"></div>
                                </td>
                                <td class="px-6 py-4"><div class="h-5 bg-slate-100 rounded-full w-14"></div></td>
                                <td class="px-6 py-4"><div class="h-5 bg-slate-100 rounded-full w-14"></div></td>
                                <td class="px-6 py-4 hidden lg:table-cell"><div class="h-4 bg-slate-100 rounded w-28"></div></td>
                            </tr>
                        @endfor
                    </tbody>

                    <!-- Table content -->
                    <tbody class="divide-y divide-slate-100 bg-white/40 hidden" id="table-content">
                        @foreach($tickets as $ticket)
                        <tr class="ticket-row group">
                            <!-- Subject & AI Summary -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-900 max-w-sm truncate leading-snug">
                                    <a href="/agent/tickets/{{ $ticket->id }}" class="ticket-row-link hover:text-indigo-650 transition">
                                        {{ $ticket->subject }}
                                    </a>
                                </div>
                                @if($ticket->ai_summary)
                                <div class="text-[10px] text-slate-400 mt-1 max-w-sm truncate">{{ $ticket->ai_summary }}</div>
                                @endif
                            </td>

                            <!-- Sender -->
                            <td class="px-6 py-4 hidden md:table-cell">
                                <div class="text-sm text-slate-800 font-medium">{{ $ticket->sender_name ?? $ticket->sender_email }}</div>
                                @if($ticket->sender_name)
                                <div class="text-[10px] text-slate-400 mt-1">{{ $ticket->sender_email }}</div>
                                @endif
                            </td>

                            <!-- Priority -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge-{{ $ticket->priority }} text-[9px] px-2.5 py-1 rounded-lg border font-bold uppercase tracking-wider">{{ $ticket->priority }}</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge-{{ $ticket->status }} text-[9px] px-2.5 py-1 rounded-lg border font-bold uppercase tracking-wider">{{ str_replace('_', ' ', $ticket->status) }}</span>
                            </td>

                            <!-- Created Date & Time -->
                            <td class="px-6 py-4 hidden lg:table-cell whitespace-nowrap text-xs text-slate-500 font-semibold">
                                {{ $ticket->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">
            {{ $tickets->appends(request()->query())->links() }}
        </div>
        @else
        <div class="bg-white/80 backdrop-blur-md border border-slate-200/80 rounded-2xl p-12 text-center shadow-xs">
            <div class="text-3xl mb-2">📭</div>
            <h2 class="text-base font-bold text-slate-900 mb-1">No tickets match search</h2>
            <p class="text-sm text-slate-500 max-w-sm mx-auto">
                Try modifying your search text or removing the filters to find the tickets.
            </p>
        </div>
        @endif
    </main>

    <script>
        function openMobileSidebar() {
            const sidebar = document.getElementById("mobile-sidebar");
            const backdrop = document.getElementById("mobile-sidebar-backdrop");
            sidebar.classList.remove("-translate-x-full");
            sidebar.classList.add("translate-x-0");
            backdrop.classList.remove("opacity-0", "pointer-events-none");
            backdrop.classList.add("opacity-100");
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById("mobile-sidebar");
            const backdrop = document.getElementById("mobile-sidebar-backdrop");
            sidebar.classList.remove("translate-x-0");
            sidebar.classList.add("-translate-x-full");
            backdrop.classList.remove("opacity-100");
            backdrop.classList.add("opacity-0", "pointer-events-none");
        }

        document.getElementById("mobile-menu-btn").addEventListener("click", openMobileSidebar);
        document.getElementById("mobile-sidebar-close").addEventListener("click", closeMobileSidebar);
        document.getElementById("mobile-sidebar-backdrop").addEventListener("click", closeMobileSidebar);

        // Transition skeleton loaders
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const skeleton = document.getElementById('table-skeleton');
                const content = document.getElementById('table-content');
                if (skeleton && content) {
                    skeleton.classList.add('hidden');
                    content.classList.remove('hidden');
                }
            }, 250);
        });
    </script>
</body>
</html>
