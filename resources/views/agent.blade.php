<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — Agent Panel</title>
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

        .ticket-row {
            position: relative;
            transition: all 0.2s ease;
            cursor: pointer;
        }
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
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-900 bg-slate-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200/80 py-4 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40">
        <a href="/agent" class="text-lg font-bold tracking-wider text-slate-900">AGENT PANEL</a>

        <!-- Desktop Navigation Actions -->
        <div class="hidden md:flex items-center gap-6" id="nav-actions-desktop">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="font-medium text-sm text-slate-700">{{ $user->name }}</span>
                <span class="bg-emerald-50 text-emerald-700 text-xs px-2 py-0.5 rounded border border-emerald-100 font-semibold uppercase tracking-wider">{{ $user->role }}</span>
            </div>
            @if($user->role === 'admin')
                <a href="/admin" class="bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium rounded-lg py-1.5 px-4 text-xs transition">Admin Panel</a>
            @endif
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-1.5 px-4 text-xs transition cursor-pointer">Sign Out</button>
            </form>
        </div>

        <!-- Mobile Menu Hamburger Button -->
        <button id="mobile-menu-btn" class="block md:hidden text-slate-500 hover:text-slate-800 focus:outline-none cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <!-- Mobile Sidebar Drawer -->
    <div id="mobile-sidebar" class="fixed top-0 left-0 bottom-0 w-72 bg-white border-r border-slate-200 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out p-6 flex flex-col gap-8">
        <div class="flex justify-between items-center">
            <span class="text-lg font-bold tracking-wider text-slate-900">AGENT PANEL</span>
            <button id="mobile-sidebar-close" class="text-slate-500 hover:text-slate-800 cursor-pointer focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 flex flex-col justify-between" id="nav-actions-mobile">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center font-semibold text-slate-700">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col">
                        <span class="font-semibold text-sm text-slate-800">{{ $user->name }}</span>
                        <span class="text-xs text-slate-500 mt-0.5">{{ $user->email }}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2 pl-1">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Role</span>
                    <div>
                        <span class="bg-emerald-50 text-emerald-700 text-xs px-2.5 py-1 rounded border border-emerald-100 font-semibold uppercase tracking-wider">{{ $user->role }}</span>
                    </div>
                </div>
                @if($user->role === 'admin')
                <a href="/admin" class="bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition text-center">Admin Panel</a>
                @endif
            </div>
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition cursor-pointer mt-auto">Sign Out</button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 py-10 px-4 md:px-8 max-w-7xl mx-auto w-full">

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">My Assigned Tickets</h1>
            <p class="text-sm text-slate-500 mt-1">Review and resolve your active support responsibilities.</p>
        </div>

        <!-- Flash Message -->
        @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-slate-950">{{ $stats['total'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Total Assigned</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['open'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Open</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-amber-600">{{ $stats['in_progress'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">In Progress</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-emerald-600">{{ $stats['resolved'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Resolved</div>
            </div>
        </div>

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
                            <th class="px-6 py-4 font-semibold hidden lg:table-cell">Created</th>
                        </tr>
                    </thead>
                    <!-- Skeleton Loader -->
                    <tbody class="divide-y divide-slate-100" id="table-skeleton">
                        @for($i = 0; $i < 3; $i++)
                            <tr class="animate-pulse">
                                <td class="px-6 py-4"><div class="h-4 bg-slate-100 rounded w-16"></div></td>
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
                                <a href="/agent/tickets/{{ $ticket->id }}" class="ticket-row-link font-mono text-sm font-semibold cursor-pointer">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>

                            <!-- Subject & AI Summary -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900 max-w-sm truncate">{{ $ticket->subject }}</div>
                                @if($ticket->ai_summary)
                                <div class="text-xs text-slate-400 mt-0.5 max-w-sm truncate">{{ $ticket->ai_summary }}</div>
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

                            <!-- Created Date -->
                            <td class="px-6 py-4 hidden lg:table-cell whitespace-nowrap">
                                <span class="text-xs text-slate-500">{{ $ticket->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-white border border-slate-200 rounded-xl p-12 text-center shadow-xs">
            <div class="text-4xl mb-3">📭</div>
            <h2 class="text-lg font-semibold text-slate-900 mb-1">No tickets assigned</h2>
            <p class="text-sm text-slate-500 max-w-sm mx-auto leading-relaxed">
                You're all caught up! There are no support tickets currently assigned to you.
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
            }, 300);
        });
    </script>
</body>
</html>
