<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — All Tickets</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .ticket-row { position: relative; transition: all 0.2s ease; cursor: pointer; }
        .ticket-row:hover {
            background: #f8fafc;
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

        .filter-select {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.875rem;
            padding: 0.5rem 2.25rem 0.5rem 0.75rem;
            border-radius: 0.5rem;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
        }
        .filter-select:focus {
            outline: none;
            border-color: #cbd5e1;
            box-shadow: 0 0 0 2px rgba(148,163,184,0.1);
        }

        /* Assign dropdown */
        .assign-select {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.8125rem;
            padding: 0.375rem 1.75rem 0.375rem 0.625rem;
            border-radius: 0.375rem;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 0.875rem;
            min-width: 130px;
        }
        .assign-select:focus {
            outline: none;
            border-color: #cbd5e1;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-900 bg-slate-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200/80 py-4 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40">
        <a href="/admin" class="text-lg font-bold tracking-wider text-slate-900">TICKET SYSTEM</a>
        
        <div class="hidden md:flex items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="font-medium text-sm text-slate-700">{{ $user->name }}</span>
            </div>
            <a href="/admin" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition">Dashboard</a>
            <a href="/admin/users" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition">Users</a>
            <a href="/admin/tickets" class="text-slate-900 text-sm font-semibold border-b-2 border-slate-900 pb-4 mt-4">Tickets</a>
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-1.5 px-4 text-xs transition cursor-pointer">Sign Out</button>
            </form>
        </div>

        <button id="mobile-menu-btn" class="block md:hidden text-slate-500 hover:text-slate-800 focus:outline-none cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <!-- Mobile Sidebar Drawer -->
    <div id="mobile-sidebar" class="fixed top-0 left-0 bottom-0 w-72 bg-white border-r border-slate-200 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out p-6 flex flex-col gap-6">
        <div class="flex justify-between items-center">
            <span class="text-lg font-bold tracking-wider text-slate-900">MENU</span>
            <button id="mobile-sidebar-close" class="text-slate-500 hover:text-slate-800 cursor-pointer focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="flex flex-col gap-4 flex-1">
            <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-semibold text-slate-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex flex-col">
                    <span class="font-semibold text-sm text-slate-800">{{ $user->name }}</span>
                    <span class="text-xs text-slate-500">{{ $user->email }}</span>
                </div>
            </div>
            <a href="/admin" class="text-slate-600 hover:text-slate-900 text-sm font-medium py-2 px-3 rounded-lg hover:bg-slate-50 transition">← Dashboard</a>
            <a href="/admin/users" class="text-slate-600 hover:text-slate-900 text-sm font-medium py-2 px-3 rounded-lg hover:bg-slate-50 transition">Manage Users</a>
            <a href="/admin/tickets" class="text-slate-900 text-sm font-semibold py-2 px-3 rounded-lg bg-slate-100">Tickets</a>
        </div>
        <form method="POST" action="/logout" class="m-0">
            @csrf
            <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition cursor-pointer">Sign Out</button>
        </form>
    </div>

    <!-- Main Content -->
    <main class="flex-1 py-10 px-4 md:px-8 max-w-7xl mx-auto w-full">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">All Support Tickets</h1>
                <p class="text-sm text-slate-500 mt-1">Review and manage support tickets received by the system.</p>
            </div>
            <div class="text-sm text-slate-400 font-medium self-start bg-slate-100 px-3 py-1 rounded-md border border-slate-200/50">
                {{ $tickets->total() }} ticket{{ $tickets->total() !== 1 ? 's' : '' }} total
            </div>
        </div>

        <!-- Flash Message -->
        @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Filters Form -->
        <form method="GET" action="/admin/tickets" class="mb-6">
            <div class="flex flex-wrap items-center gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                <!-- Extended Search Input -->
                <div class="relative w-full md:flex-1 min-w-[280px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search tickets by number, subject, or sender..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 py-2 pl-9 pr-4 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition">
                </div>

                <!-- Category Filter -->
                <select name="category" class="filter-select">
                    <option value="">All Categories</option>
                    <option value="General" {{ ($filters['category'] ?? '') === 'General' ? 'selected' : '' }}>General</option>
                    <option value="Refund" {{ ($filters['category'] ?? '') === 'Refund' ? 'selected' : '' }}>Refund</option>
                    <option value="Technical" {{ ($filters['category'] ?? '') === 'Technical' ? 'selected' : '' }}>Technical</option>
                </select>

                <!-- Status Filter -->
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>

                <!-- Priority Filter -->
                <select name="priority" class="filter-select">
                    <option value="">All Priorities</option>
                    <option value="urgent" {{ ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                </select>

                <!-- Assignee Filter -->
                <select name="assigned_to" class="filter-select">
                    <option value="">All Agents</option>
                    <option value="unassigned" {{ ($filters['assigned_to'] ?? '') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ ($filters['assigned_to'] ?? '') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>

                <!-- Filter Actions -->
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2 px-5 text-sm transition cursor-pointer">
                    Apply
                </button>

                @if(array_filter($filters))
                <a href="/admin/tickets" class="text-slate-400 hover:text-slate-600 text-sm font-medium transition pl-1">Reset</a>
                @endif
            </div>
        </form>

        <!-- Tickets Table Card -->
        @if($tickets->count() > 0)
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 text-xs uppercase tracking-wider bg-slate-50/70">
                            <th class="px-6 py-4 font-semibold">Ticket</th>
                            <th class="px-6 py-4 font-semibold">Subject</th>
                            <th class="px-6 py-4 font-semibold hidden md:table-cell">Sender</th>
                            <th class="px-6 py-4 font-semibold">Priority</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold hidden lg:table-cell">Category</th>
                            <th class="px-6 py-4 font-semibold hidden lg:table-cell">Created</th>
                        </tr>
                    </thead>
                    <!-- Skeleton Loader -->
                    <tbody class="divide-y divide-slate-100" id="table-skeleton">
                        @for($i = 0; $i < 4; $i++)
                            <tr class="animate-pulse">
                                <td class="px-6 py-4"><div class="h-4 bg-slate-100 rounded w-16"></div></td>
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-slate-100 rounded w-48 mb-1.5"></div>
                                    <div class="h-3 bg-slate-100 rounded w-20"></div>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="h-4 bg-slate-100 rounded w-28 mb-1.5"></div>
                                    <div class="h-3 bg-slate-100 rounded w-36"></div>
                                </td>
                                <td class="px-6 py-4"><div class="h-5 bg-slate-100 rounded-full w-14"></div></td>
                                <td class="px-6 py-4"><div class="h-5 bg-slate-100 rounded-full w-14"></div></td>
                                <td class="px-6 py-4 hidden lg:table-cell"><div class="h-6 bg-slate-100 rounded w-24"></div></td>
                                <td class="px-6 py-4 hidden lg:table-cell"><div class="h-4 bg-slate-100 rounded w-20"></div></td>
                            </tr>
                        @endfor
                    </tbody>

                    <!-- Actual Content -->
                    <tbody class="divide-y divide-slate-100 hidden" id="table-content">
                        @foreach($tickets as $ticket)
                        <tr class="ticket-row">
                            <!-- Ticket Number -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="/admin/tickets/{{ $ticket->id }}" class="ticket-row-link font-mono text-sm font-semibold text-slate-900">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            
                            <!-- Subject & Category -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900 max-w-xs truncate block">
                                    {{ $ticket->subject }}
                                </div>
                                @if($ticket->category)
                                <div class="text-xs text-indigo-600 font-semibold mt-0.5">{{ $ticket->category }}</div>
                                @endif
                            </td>

                            <!-- Sender -->
                            <td class="px-6 py-4 hidden md:table-cell">
                                <div class="text-sm text-slate-800">{{ $ticket->sender_name ?? $ticket->sender_email }}</div>
                                @if($ticket->sender_name)
                                <div class="text-xs text-slate-400 mt-0.5">{{ $ticket->sender_email }}</div>
                                @endif
                            </td>

                            <!-- Priority -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge-{{ $ticket->priority }} text-xs px-2.5 py-0.5 rounded border font-semibold uppercase tracking-wider">{{ $ticket->priority }}</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge-{{ $ticket->status }} text-xs px-2.5 py-0.5 rounded border font-semibold capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                            </td>

                            <!-- Category Column -->
                            <td class="px-6 py-4 hidden lg:table-cell whitespace-nowrap">
                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-xs px-2.5 py-1 rounded font-semibold">
                                    {{ $ticket->category ?? 'General' }}
                                </span>
                            </td>

                            <!-- Created Date -->
                            <td class="px-6 py-4 hidden lg:table-cell whitespace-nowrap">
                                <span class="text-xs text-slate-500">{{ $ticket->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Container -->
            @if($tickets->hasPages())
            <div class="border-t border-slate-100 px-6 py-4 flex justify-center bg-slate-50/50">
                {{ $tickets->appends($filters)->links('pagination::simple-tailwind') }}
            </div>
            @endif
        </div>
        @else
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-xs">
            <div class="text-4xl mb-3">📭</div>
            <h2 class="text-lg font-semibold text-slate-900 mb-1">No tickets found</h2>
            <p class="text-sm text-slate-500 max-w-sm mx-auto leading-relaxed">
                @if(array_filter($filters))
                    No tickets match the selected filters. <a href="/admin/tickets" class="text-indigo-600 hover:underline">Clear filters</a>
                @else
                    No tickets have been registered in the database yet. Unread emails received at your support address are automatically imported here.
                @endif
            </p>
        </div>
        @endif
    </main>

    <script>
        function openMobileSidebar() {
            document.getElementById("mobile-sidebar").classList.replace("-translate-x-full", "translate-x-0");
            const backdrop = document.getElementById("mobile-sidebar-backdrop");
            backdrop.classList.remove("opacity-0", "pointer-events-none");
            backdrop.classList.add("opacity-100");
        }
        function closeMobileSidebar() {
            document.getElementById("mobile-sidebar").classList.replace("translate-x-0", "-translate-x-full");
            const backdrop = document.getElementById("mobile-sidebar-backdrop");
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
            }, 300);
        });
    </script>
</body>
</html>
