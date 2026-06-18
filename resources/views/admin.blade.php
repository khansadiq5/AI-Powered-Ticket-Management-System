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
    <nav class="bg-white border-b border-slate-200/80 py-4 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40">
        <a href="/admin" class="text-lg font-bold tracking-wider text-slate-900">TICKET SYSTEM</a>
        
        <!-- Desktop Navigation Actions -->
        <div class="hidden md:flex items-center gap-6" id="nav-actions-desktop">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="font-medium text-sm text-slate-700">{{ $user->name }}</span>
                <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-sm font-semibold uppercase tracking-wider border border-slate-200/50">{{ $user->role }}</span>
            </div>
            <a href="/admin/users" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition">Users</a>
            <a href="/admin/tickets" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition">Tickets</a>
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
            <span class="text-lg font-bold tracking-wider text-slate-900">MENU</span>
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
                        <span class="bg-slate-100 text-slate-600 text-xs px-2.5 py-1 rounded-sm font-semibold uppercase tracking-wider border border-slate-200/50">{{ $user->role }}</span>
                    </div>
                </div>
                <a href="/admin/users" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition text-center">Manage Users</a>
                <a href="/admin/tickets" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition text-center">All Tickets</a>
            </div>
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition cursor-pointer mt-auto">Sign Out</button>
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
