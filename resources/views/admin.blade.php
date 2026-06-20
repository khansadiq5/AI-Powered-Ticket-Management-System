<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
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
                <span class="font-extrabold text-slate-950">TICKET</span>
                <span class="font-light text-slate-400">SYSTEM</span>
            </span>
        </a>
        
        <!-- Center: Desktop Navigation Links -->
        <div class="hidden md:flex items-center gap-1 h-full">
            @if($user->role === 'admin')
                <a href="/admin" class="{{ Request::is('admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Dashboard</a>
                <a href="/admin/users" class="{{ Request::is('admin/users*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Users</a>
                <a href="/admin/tickets" class="{{ Request::is('admin/tickets*') || (Request::is('agent/tickets*') && $user->role === 'admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-655 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Tickets</a>
            @else
                <a href="/agent" class="bg-slate-900 text-white font-semibold shadow-xs text-sm px-4 py-2 rounded-xl transition duration-250">Dashboard</a>
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
        <button id="mobile-menu-btn" class="block md:hidden p-2 rounded-xl text-slate-500 hover:text-slate-850 hover:bg-slate-50 cursor-pointer focus:outline-hidden transition">
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
                        <span class="text-xs text-slate-500 truncate mt-0.5">{{ $user->email }}</span>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 mb-2">Navigation</span>
                    @if($user->role === 'admin')
                        <a href="/admin" class="{{ Request::is('admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-55 font-medium' }} text-sm py-2.5 px-3 rounded-xl transition">Dashboard</a>
                        <a href="/admin/users" class="{{ Request::is('admin/users*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-55 font-medium' }} text-sm py-2.5 px-3 rounded-xl transition">Manage Users</a>
                        <a href="/admin/tickets" class="{{ Request::is('admin/tickets*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-55 font-medium' }} text-sm py-2.5 px-3 rounded-xl transition">Tickets</a>
                    @else
                        <a href="/agent" class="bg-slate-900 text-white font-semibold shadow-xs text-sm py-2.5 px-3 rounded-xl transition">Dashboard</a>
                    @endif
                </div>
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

        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-955 leading-none">
                    Welcome, {{ $user->name }}
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed mt-2">
                    Overview of your support system activity and AI efficiency.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/admin/tickets" class="bg-slate-955 hover:bg-slate-900 text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-xs transition duration-200 flex items-center gap-1.5">
                    View Tickets Drawer
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <!-- Ticket Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-5 text-center shadow-xs">
                <div class="text-3xl font-extrabold text-slate-950 leading-none">{{ $ticketStats['total'] }}</div>
                <div class="text-[10px] text-slate-400 mt-2 uppercase tracking-widest font-bold">Total Tickets</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-5 text-center shadow-xs">
                <div class="text-3xl font-extrabold text-blue-600 leading-none">{{ $ticketStats['open'] }}</div>
                <div class="text-[10px] text-slate-400 mt-2 uppercase tracking-widest font-bold">Open</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-5 text-center shadow-xs">
                <div class="text-3xl font-extrabold text-amber-600 leading-none">{{ $ticketStats['in_progress'] }}</div>
                <div class="text-[10px] text-slate-400 mt-2 uppercase tracking-widest font-bold">In Progress</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-5 text-center shadow-xs">
                <div class="text-3xl font-extrabold text-emerald-600 leading-none">{{ $ticketStats['resolved'] }}</div>
                <div class="text-[10px] text-slate-400 mt-2 uppercase tracking-widest font-bold">Resolved</div>
            </div>
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-5 text-center shadow-xs col-span-2 md:col-span-1">
                <div class="text-3xl font-extrabold text-rose-600 leading-none">{{ $ticketStats['unassigned'] }}</div>
                <div class="text-[10px] text-slate-400 mt-2 uppercase tracking-widest font-bold">Unassigned</div>
            </div>
        </div>

        <!-- AI & Performance Analytics Dashboard -->
        <div class="mb-8">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">AI & SLA Performance Analytics</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- AI Resolved Count -->
                <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-indigo-50 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50/80 border border-indigo-100 flex items-center justify-center text-xl relative z-10">🤖</div>
                    <div class="relative z-10">
                        <div class="text-2xl font-extrabold text-indigo-955 leading-none">{{ $resolvedByAiCount }}</div>
                        <div class="text-[10px] text-slate-400 mt-1.5 font-bold uppercase tracking-widest">Resolved by AI</div>
                    </div>
                </div>

                <!-- % Resolved by AI -->
                <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-emerald-50 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50/80 border border-emerald-100 flex items-center justify-center text-xl relative z-10">⚡</div>
                    <div class="relative z-10">
                        <div class="text-2xl font-extrabold text-emerald-955 leading-none">{{ $percentageSolvedByAi }}%</div>
                        <div class="text-[10px] text-slate-400 mt-1.5 font-bold uppercase tracking-widest">AI Success Rate</div>
                    </div>
                </div>

                <!-- Average SLA Resolution Time -->
                <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-amber-50 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50/80 border border-amber-100 flex items-center justify-center text-xl relative z-10">⏱️</div>
                    <div class="relative z-10">
                        <div class="text-2xl font-extrabold text-amber-955 leading-none">{{ $averageResolutionTime }}</div>
                        <div class="text-[10px] text-slate-400 mt-1.5 font-bold uppercase tracking-widest">Avg Resolution Time</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Visualization Section -->
        <div class="mb-8">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 font-semibold">Visual Analytics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ticket Volume Trend Chart -->
                <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Ticket Volume Trend (Last 7 Days)</h3>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <!-- Status Distribution Chart -->
                <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Ticket Status Distribution</h3>
                    <div class="h-64 flex justify-center items-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
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

        // Trend Line Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [{
                    label: 'New Tickets',
                    data: @json($trendData),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.04)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2,
                    pointBackgroundColor: '#4f46e5',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.5)' },
                        ticks: { stepSize: 1, font: { family: 'Outfit', size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Outfit', size: 10 } }
                    }
                }
            }
        });

        // Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    data: [
                        {{ $statusCounts['open'] }},
                        {{ $statusCounts['in_progress'] }},
                        {{ $statusCounts['resolved'] }},
                        {{ $statusCounts['closed'] }}
                    ],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#64748b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { family: 'Outfit', size: 11 } }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
