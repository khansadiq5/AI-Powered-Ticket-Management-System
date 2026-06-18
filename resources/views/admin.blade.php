<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-950 text-slate-100 min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-white/5 py-4 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40">
        <a href="/admin" class="text-xl font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent hover:opacity-90 transition">TICKET MANAGEMENT</a>
        
        <!-- Desktop Navigation Actions -->
        <div class="hidden md:flex items-center gap-6" id="nav-actions-desktop">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center font-semibold text-sm text-white shadow-[0_0_10px_rgba(99,102,241,0.4)]">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <span class="font-medium text-sm text-white">{{ $user->name }}</span>
                <span class="bg-indigo-500/15 text-indigo-300 text-xs px-2 py-0.5 rounded font-semibold uppercase tracking-wider">{{ $user->role }}</span>
            </div>
            <a href="/admin/users" class="text-slate-300 hover:text-white text-sm font-medium transition">Users</a>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="bg-white/5 hover:bg-white/10 border border-white/5 text-slate-200 font-medium rounded-lg py-1.5 px-4 text-xs transition cursor-pointer">Sign Out</button>
            </form>
        </div>

        <!-- Mobile Menu Hamburger Button -->
        <button id="mobile-menu-btn" class="block md:hidden text-slate-400 hover:text-white focus:outline-none cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 transition-opacity duration-300 opacity-0 pointer-events-none"></div>

    <!-- Mobile Sidebar Drawer -->
    <div id="mobile-sidebar" class="fixed top-0 left-0 bottom-0 w-72 bg-slate-900 border-r border-white/5 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out p-6 flex flex-col gap-8">
        <div class="flex justify-between items-center">
            <span class="text-lg font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">TICKET MANAGEMENT</span>
            <button id="mobile-sidebar-close" class="text-slate-400 hover:text-white cursor-pointer focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 flex flex-col justify-between" id="nav-actions-mobile">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-3 bg-white/5 p-4 rounded-xl border border-white/5">
                    <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center font-semibold text-white shadow-[0_0_10px_rgba(99,102,241,0.4)]">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col">
                        <span class="font-semibold text-sm text-white">{{ $user->name }}</span>
                        <span class="text-xs text-slate-400 mt-0.5">{{ $user->email }}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2 pl-1">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</span>
                    <div>
                        <span class="bg-indigo-500/15 text-indigo-300 text-xs px-2.5 py-1 rounded font-semibold uppercase tracking-wider">{{ $user->role }}</span>
                    </div>
                </div>
                <a href="/admin/users" class="bg-indigo-500/15 hover:bg-indigo-500/25 border border-indigo-500/30 text-indigo-300 font-medium rounded-lg py-3 px-4 text-sm transition text-center">Manage Users</a>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="w-full bg-white/5 hover:bg-white/10 border border-white/5 text-slate-200 font-medium rounded-lg py-3 px-4 text-sm transition cursor-pointer mt-auto">Sign Out</button>
            </form>
        </div>
    </div>

    <!-- Hero / Main Layout -->
    <main class="flex-1 flex flex-col items-center justify-center py-12 px-6 text-center">
        <div class="max-w-2xl flex flex-col gap-6">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-white leading-tight">
                Welcome Back, <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">{{ $user->name }}</span>
            </h1>
            <p class="text-base md:text-lg text-slate-400 max-w-xl mx-auto leading-relaxed">
                Access your administrator command panel and manage configurations, agent routing, and ticket rules.
            </p>
        </div>

        <!-- Admin Control Cards -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl">
            <div class="bg-slate-900/50 backdrop-blur-lg border border-white/5 rounded-2xl p-6 text-left flex flex-col gap-4 transition hover:border-indigo-500/40 hover:shadow-2xl hover:shadow-indigo-500/5">
                <div class="text-3xl">👥</div>
                <div class="text-lg font-semibold text-white">Manage Users</div>
                <div class="text-sm text-slate-400 leading-relaxed">Create, edit, and deactivate agent and administrator accounts across the workspace.</div>
            </div>
            <div class="bg-slate-900/50 backdrop-blur-lg border border-white/5 rounded-2xl p-6 text-left flex flex-col gap-4 transition hover:border-indigo-500/40 hover:shadow-2xl hover:shadow-indigo-500/5">
                <div class="text-3xl">⚙️</div>
                <div class="text-lg font-semibold text-white">System Settings</div>
                <div class="text-sm text-slate-400 leading-relaxed">Configure email endpoints, ticket assignment algorithms, and Gemini API keys.</div>
            </div>
            <div class="bg-slate-900/50 backdrop-blur-lg border border-white/5 rounded-2xl p-6 text-left flex flex-col gap-4 transition hover:border-indigo-500/40 hover:shadow-2xl hover:shadow-indigo-500/5">
                <div class="text-3xl">📝</div>
                <div class="text-lg font-semibold text-white">System Logs</div>
                <div class="text-sm text-slate-400 leading-relaxed">Audit recent agent actions, auto-responses draft records, and API communication logs.</div>
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
    </script>
</body>
</html>
