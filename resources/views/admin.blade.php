<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
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
    <main class="flex-1 py-10 px-6 md:px-8 max-w-7xl mx-auto w-full">

        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 leading-tight">
                Welcome, {{ $user->name }}
            </h1>
            <p class="text-base text-slate-500 leading-relaxed mt-2">
                Overview of ticket activity and administration dashboard.
            </p>
        </div>

        <!-- Ticket Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-10">
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-slate-950">{{ $ticketStats['total'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Total</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-blue-600">{{ $ticketStats['open'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Open</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-amber-600">{{ $ticketStats['in_progress'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">In Progress</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs">
                <div class="text-3xl font-bold text-emerald-600">{{ $ticketStats['resolved'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Resolved</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-center shadow-xs col-span-2 md:col-span-1">
                <div class="text-3xl font-bold text-rose-600">{{ $ticketStats['unassigned'] }}</div>
                <div class="text-xs text-slate-500 mt-1.5 uppercase tracking-wider font-semibold">Unassigned</div>
            </div>
        </div>

        <!-- AI & Performance Analytics Dashboard -->
        <div class="mb-10">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">AI & SLA Performance Analytics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- AI Resolved Count -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-indigo-50 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl relative z-10">🤖</div>
                    <div class="relative z-10">
                        <div class="text-2xl font-bold text-indigo-950">{{ $resolvedByAiCount }}</div>
                        <div class="text-xs text-slate-500 mt-0.5 font-semibold uppercase tracking-wider">Resolved by AI</div>
                    </div>
                </div>

                <!-- % Resolved by AI -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-emerald-50 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-2xl relative z-10">⚡</div>
                    <div class="relative z-10">
                        <div class="text-2xl font-bold text-emerald-950">{{ $percentageSolvedByAi }}%</div>
                        <div class="text-xs text-slate-500 mt-0.5 font-semibold uppercase tracking-wider">AI Success Rate</div>
                    </div>
                </div>

                <!-- Average SLA Resolution Time -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-amber-50 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-2xl relative z-10">⏱️</div>
                    <div class="relative z-10">
                        <div class="text-2xl font-bold text-amber-950">{{ $averageResolutionTime }}</div>
                        <div class="text-xs text-slate-500 mt-0.5 font-semibold uppercase tracking-wider">Avg Resolution Time</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Control Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="/admin/users" class="bg-white border border-slate-200 rounded-xl p-6 text-left flex flex-col gap-3 shadow-xs hover:border-slate-300 transition group">
                <div class="text-2xl group-hover:scale-105 transition-transform duration-200 self-start">👥</div>
                <div class="text-base font-bold text-slate-900">Manage Users</div>
                <div class="text-sm text-slate-500 leading-relaxed">Create and manage accounts for agents and administrators in the system.</div>
            </a>
            <a href="/admin/tickets" class="bg-white border border-slate-200 rounded-xl p-6 text-left flex flex-col gap-3 shadow-xs hover:border-slate-300 transition group">
                <div class="text-2xl group-hover:scale-105 transition-transform duration-200 self-start">🎫</div>
                <div class="text-base font-bold text-slate-900">All Tickets</div>
                <div class="text-sm text-slate-500 leading-relaxed">View all student support tickets, filter, search, and assign agent responsibilities.</div>
            </a>
            <a href="/agent" class="bg-white border border-slate-200 rounded-xl p-6 text-left flex flex-col gap-3 shadow-xs hover:border-slate-300 transition group">
                <div class="text-2xl group-hover:scale-105 transition-transform duration-200 self-start">👤</div>
                <div class="text-base font-bold text-slate-900">Agent View</div>
                <div class="text-sm text-slate-500 leading-relaxed">Switch to the agent panel to resolve your assigned ticket queries.</div>
            </a>
        </div>
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
    </script>
</body>
</html>
