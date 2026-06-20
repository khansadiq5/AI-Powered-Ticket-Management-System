<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — Users</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
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
                <span class="font-extrabold text-slate-955">TICKET</span>
                <span class="font-light text-slate-400">SYSTEM</span>
            </span>
        </a>
        
        <!-- Center: Desktop Navigation Links -->
        <div class="hidden md:flex items-center gap-1 h-full">
            @if($user->role === 'admin')
                <a href="/admin" class="{{ Request::is('admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Dashboard</a>
                <a href="/admin/users" class="{{ Request::is('admin/users*') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Users</a>
                <a href="/admin/tickets" class="{{ Request::is('admin/tickets*') || (Request::is('agent/tickets*') && $user->role === 'admin') ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 font-medium' }} text-sm px-4 py-2 rounded-xl transition duration-250">Tickets</a>
            @else
                <a href="/agent" class="bg-slate-900 text-white font-semibold shadow-xs text-sm px-4 py-2 rounded-xl transition duration-250">My Tickets</a>
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
                <button type="submit" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-755 font-bold rounded-xl py-2 px-4 text-xs transition cursor-pointer">Sign Out</button>
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
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 mb-2">Navigation</span>
                    @if($user->role === 'admin')
                        <a href="/admin" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Dashboard</a>
                        <a href="/admin/users" class="bg-slate-900 text-white font-semibold shadow-xs text-sm py-2.5 px-3 rounded-xl transition">Manage Users</a>
                        <a href="/admin/tickets" class="text-slate-600 hover:text-slate-900 hover:bg-slate-50 font-medium text-sm py-2.5 px-3 rounded-xl transition">Tickets</a>
                    @else
                        <a href="/agent" class="bg-slate-900 text-white font-semibold shadow-xs text-sm py-2.5 px-3 rounded-xl transition">My Tickets</a>
                    @endif
                </div>
            </div>
            
            <!-- Sign out -->
            <form method="POST" action="/logout" class="m-0 border-t border-slate-100 pt-4">
                @csrf
                <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-755 font-bold rounded-xl py-3 px-4 text-sm transition cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign Out
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 py-8 px-4 md:px-8 max-w-7xl mx-auto w-full relative z-10">

        <!-- Page Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-955 leading-none">User Accounts</h1>
                <p class="text-sm text-slate-500 mt-2">Create, modify, and monitor administrators and support agents.</p>
            </div>
            <button onclick="openModal('add-user-modal')" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl py-2.5 px-5 text-xs transition duration-150 cursor-pointer flex items-center gap-1.5 self-start shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create User
            </button>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200/60 text-emerald-800 px-4 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-550 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-rose-50 border border-rose-200/60 text-rose-800 px-4 py-3 rounded-xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-550 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Add User Modal -->
        <div id="add-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs" onclick="closeModal('add-user-modal')"></div>
            
            <!-- Content -->
            <div class="modal-card relative bg-white/90 backdrop-blur-xl border border-slate-200/80 rounded-2xl w-full max-w-md p-6 shadow-2xl transform scale-95 opacity-0 transition-all duration-300 z-10">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-base font-extrabold text-slate-950">Create Support Account</h2>
                    <button onclick="closeModal('add-user-modal')" class="text-slate-400 hover:text-slate-650 cursor-pointer p-1 rounded-lg hover:bg-slate-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form method="POST" action="/admin/users" class="space-y-4">
                    @csrf
                    <!-- Name -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Full Name</label>
                        <input type="text" name="name" value="{{ old('_method') !== 'PUT' ? old('name') : '' }}" placeholder="John Doe" required
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                        @if($errors->has('name') && old('_method') !== 'PUT')
                            <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email Address</label>
                        <input type="email" name="email" value="{{ old('_method') !== 'PUT' ? old('email') : '' }}" placeholder="john@example.com" required
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                        @if($errors->has('email') && old('_method') !== 'PUT')
                            <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('email') }}</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Password -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Password</label>
                            <input type="password" name="password" placeholder="Min 8 characters" required
                                   class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                            @if($errors->has('password') && old('_method') !== 'PUT')
                                <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('password') }}</span>
                            @endif
                        </div>

                        <!-- Role -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Access Role</label>
                            <div class="relative w-full">
                                <select name="role" required
                                        class="custom-select w-full py-2.5 text-xs text-slate-900">
                                    <option value="agent" {{ (old('_method') !== 'PUT' ? old('role') : '') === 'agent' ? 'selected' : '' }}>Agent</option>
                                    <option value="admin" {{ (old('_method') !== 'PUT' ? old('role') : '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                            @if($errors->has('role') && old('_method') !== 'PUT')
                                <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('role') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-4 justify-end">
                        <button type="button" onclick="closeModal('add-user-modal')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-4 text-xs transition">Cancel</button>
                        <button type="submit" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl py-2 px-5 text-xs transition shadow-sm">Save Account</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="edit-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-xs" onclick="closeModal('edit-user-modal')"></div>
            
            <!-- Content -->
            <div class="modal-card relative bg-white/90 backdrop-blur-xl border border-slate-200/80 rounded-2xl w-full max-w-md p-6 shadow-2xl transform scale-95 opacity-0 transition-all duration-300 z-10">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-base font-extrabold text-slate-955">Modify Support Account</h2>
                    <button onclick="closeModal('edit-user-modal')" class="text-slate-400 hover:text-slate-655 cursor-pointer p-1 rounded-lg hover:bg-slate-55 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form method="POST" action="" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" value="{{ old('_method') === 'PUT' ? old('user_id') : '' }}">
                    
                    <!-- Name -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Full Name</label>
                        <input type="text" name="name" value="{{ old('_method') === 'PUT' ? old('name') : '' }}" placeholder="John Doe" required
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                        @if($errors->has('name') && old('_method') === 'PUT')
                            <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email Address</label>
                        <input type="email" name="email" value="{{ old('_method') === 'PUT' ? old('email') : '' }}" placeholder="john@example.com" required
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                        @if($errors->has('email') && old('_method') === 'PUT')
                            <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('email') }}</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Password -->
                        <div class="flex flex-col gap-1.5">
                            <div class="flex justify-between items-center">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Password</label>
                                <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">(Optional)</span>
                            </div>
                            <input type="password" name="password" placeholder="Leave blank to preserve"
                                   class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition">
                            @if($errors->has('password') && old('_method') === 'PUT')
                                <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('password') }}</span>
                            @endif
                        </div>

                        <!-- Role -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Access Role</label>
                            <div class="relative w-full">
                                <select name="role" required
                                        class="custom-select w-full py-2.5 text-xs text-slate-900">
                                    <option value="agent" {{ (old('_method') === 'PUT' ? old('role') : '') === 'agent' ? 'selected' : '' }}>Agent</option>
                                    <option value="admin" {{ (old('_method') === 'PUT' ? old('role') : '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>
                            @if($errors->has('role') && old('_method') === 'PUT')
                                <span class="text-rose-600 text-[10px] mt-1 font-bold">{{ $errors->first('role') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-4 justify-end">
                        <button type="button" onclick="closeModal('edit-user-modal')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl py-2 px-4 text-xs transition">Cancel</button>
                        <button type="submit" class="bg-slate-950 hover:bg-slate-900 text-white font-bold rounded-xl py-2 px-5 text-xs transition shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="bg-white/80 backdrop-blur-md border border-slate-200/70 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200/60 text-slate-400 text-[10px] uppercase tracking-widest bg-slate-50/50">
                            <th class="px-6 py-4 font-bold">User</th>
                            <th class="px-6 py-4 font-bold">Email</th>
                            <th class="px-6 py-4 font-bold">Role</th>
                            <th class="px-6 py-4 font-bold">Created</th>
                            <th class="px-6 py-4 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <!-- Skeleton Loader -->
                    <tbody class="divide-y divide-slate-100 bg-white" id="table-skeleton">
                        @for($i = 0; $i < 3; $i++)
                            <tr class="animate-pulse">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100"></div>
                                        <div class="h-4 bg-slate-100 rounded w-24"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><div class="h-4 bg-slate-100 rounded w-36"></div></td>
                                <td class="px-6 py-4"><div class="h-5 bg-slate-100 rounded-full w-16"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-slate-100 rounded w-20"></div></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <div class="w-7 h-7 bg-slate-100 rounded-lg"></div>
                                        <div class="w-7 h-7 bg-slate-100 rounded-lg"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                    
                    <!-- Actual Content -->
                    <tbody class="divide-y divide-slate-100 bg-white/40 hidden" id="table-content">
                        @forelse($users as $u)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-205 flex items-center justify-center font-bold text-xs text-slate-700 flex-shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                            {{ $u->name }}
                                            @if($u->id === $user->id)
                                                <span class="text-[10px] text-slate-400 font-normal italic">(you)</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 font-medium whitespace-nowrap">{{ $u->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($u->role === 'admin')
                                        <span class="bg-slate-950 text-white text-[9px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Admin</span>
                                    @else
                                        <span class="bg-emerald-50 text-emerald-800 border border-emerald-100 text-[9px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Agent</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400 font-semibold whitespace-nowrap">{{ $u->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit button -->
                                        <button onclick="editUser({{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role]) }})" 
                                                class="text-slate-500 hover:text-slate-900 bg-white border border-slate-200 p-2 rounded-xl transition cursor-pointer flex items-center justify-center hover:shadow-xs"
                                                title="Edit User">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Delete button -->
                                        @if($u->id !== $user->id)
                                            <form method="POST" action="/admin/users/{{ $u->id }}" onsubmit="return confirm('Delete support account for {{ $u->name }}? This is permanent.')" class="inline-block m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-650 hover:text-rose-700 bg-white border border-slate-200 p-2 rounded-xl transition cursor-pointer flex items-center justify-center hover:shadow-xs" title="Delete User">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="w-8 text-center text-slate-400 text-xs flex items-center justify-center font-semibold">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400 bg-slate-50/20">No accounts configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            const card = modal.querySelector('.modal-card');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100', 'pointer-events-auto');
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            const card = modal.querySelector('.modal-card');
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            modal.classList.remove('opacity-100', 'pointer-events-auto');
            modal.classList.add('opacity-0', 'pointer-events-none');
        }

        function editUser(user) {
            const modal = document.getElementById('edit-user-modal');
            const form = modal.querySelector('form');
            
            // Set action URL and values
            form.action = `/admin/users/${user.id}`;
            modal.querySelector('[name="user_id"]').value = user.id;
            modal.querySelector('[name="name"]').value = user.name;
            modal.querySelector('[name="email"]').value = user.email;
            modal.querySelector('[name="role"]').value = user.role;
            modal.querySelector('[name="password"]').value = '';
            
            openModal('edit-user-modal');
        }

        // Keep modal open if validation errors are active
        @if($errors->any())
            @if(old('_method') === 'PUT')
                document.addEventListener('DOMContentLoaded', () => {
                    const user = {
                        id: "{{ old('user_id') }}",
                        name: "{{ old('name') }}",
                        email: "{{ old('email') }}",
                        role: "{{ old('role') }}"
                    };
                    editUser(user);
                });
            @else
                document.addEventListener('DOMContentLoaded', () => {
                    openModal('add-user-modal');
                });
            @endif
        @endif

        // Trigger mobile sidebar drawer
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
