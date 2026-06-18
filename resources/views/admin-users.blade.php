<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — Users</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { font-family: 'Outfit', sans-serif; }</style>
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
            <a href="/admin/users" class="text-slate-900 text-sm font-semibold border-b-2 border-slate-900 pb-4 mt-4">Users</a>
            <a href="/admin/tickets" class="text-slate-600 hover:text-slate-900 text-sm font-medium transition">Tickets</a>
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
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
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
            <a href="/admin/users" class="text-slate-900 text-sm font-semibold py-2 px-3 rounded-lg bg-slate-100">Manage Users</a>
            <a href="/admin/tickets" class="text-slate-600 hover:text-slate-900 text-sm font-medium py-2 px-3 rounded-lg hover:bg-slate-50 transition">Tickets</a>
        </div>
        <form method="POST" action="/logout" class="m-0">
            @csrf
            <button type="submit" class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-3 px-4 text-sm transition cursor-pointer">Sign Out</button>
        </form>
    </div>

    <!-- Main Content -->
    <main class="flex-1 py-10 px-4 md:px-8 max-w-7xl mx-auto w-full">

        <!-- Page Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">User Management</h1>
                <p class="text-slate-500 text-sm mt-1">Manage admin and agent accounts.</p>
            </div>
            <button onclick="openModal('add-user-modal')" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-5 text-sm transition cursor-pointer flex items-center gap-2 self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add User
            </button>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Add User Modal -->
        <div id="add-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeModal('add-user-modal')"></div>
            
            <!-- Content -->
            <div class="modal-card relative bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-xl transform scale-95 opacity-0 transition-all duration-300 z-10">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-semibold text-slate-900">Create New User</h2>
                    <button onclick="closeModal('add-user-modal')" class="text-slate-400 hover:text-slate-600 cursor-pointer transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form method="POST" action="/admin/users">
                    @csrf
                    <div class="space-y-4">
                        <!-- Name -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Full Name</label>
                            <div class="relative flex items-center">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <input type="text" name="name" value="{{ old('_method') !== 'PUT' ? old('name') : '' }}" placeholder="John Doe" required
                                       class="w-full bg-slate-50 border rounded-lg pl-12 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200">
                            </div>
                            @if($errors->has('name') && old('_method') !== 'PUT')
                                <span class="text-rose-600 text-xs mt-1">{{ $errors->first('name') }}</span>
                            @endif
                        </div>

                        <!-- Email -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Email Address</label>
                            <div class="relative flex items-center">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <input type="email" name="email" value="{{ old('_method') !== 'PUT' ? old('email') : '' }}" placeholder="john@example.com" required
                                       class="w-full bg-slate-50 border rounded-lg pl-12 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200">
                            </div>
                            @if($errors->has('email') && old('_method') !== 'PUT')
                                <span class="text-rose-600 text-xs mt-1">{{ $errors->first('email') }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Password -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Password</label>
                                <div class="relative flex items-center">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    </div>
                                    <input type="password" name="password" placeholder="Min 8 chars" required
                                           class="w-full bg-slate-50 border rounded-lg pl-12 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200">
                                </div>
                                @if($errors->has('password') && old('_method') !== 'PUT')
                                    <span class="text-rose-600 text-xs mt-1">{{ $errors->first('password') }}</span>
                                @endif
                            </div>

                            <!-- Role -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</label>
                                <div class="relative flex items-center">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </div>
                                    <select name="role" required
                                            class="w-full bg-slate-50 border rounded-lg pl-12 pr-10 py-2.5 text-sm text-slate-900 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200 appearance-none cursor-pointer">
                                        <option value="agent" {{ (old('_method') !== 'PUT' ? old('role') : '') === 'agent' ? 'selected' : '' }}>Agent</option>
                                        <option value="admin" {{ (old('_method') !== 'PUT' ? old('role') : '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <!-- Custom Dropdown Arrow -->
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                @if($errors->has('role') && old('_method') !== 'PUT')
                                    <span class="text-rose-600 text-xs mt-1">{{ $errors->first('role') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6 justify-end">
                        <button type="button" onclick="closeModal('add-user-modal')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg py-2.5 px-5 text-sm transition cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-5 text-sm transition cursor-pointer">Create User</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="edit-user-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeModal('edit-user-modal')"></div>
            
            <!-- Content -->
            <div class="modal-card relative bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-xl transform scale-95 opacity-0 transition-all duration-300 z-10">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-semibold text-slate-900">Edit User</h2>
                    <button onclick="closeModal('edit-user-modal')" class="text-slate-400 hover:text-slate-600 cursor-pointer transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" value="{{ old('_method') === 'PUT' ? old('user_id') : '' }}">
                    
                    <div class="space-y-4">
                        <!-- Name -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Full Name</label>
                            <div class="relative flex items-center">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <input type="text" name="name" value="{{ old('_method') === 'PUT' ? old('name') : '' }}" placeholder="John Doe" required
                                       class="w-full bg-slate-50 border rounded-lg pl-12 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200">
                            </div>
                            @if($errors->has('name') && old('_method') === 'PUT')
                                <span class="text-rose-600 text-xs mt-1">{{ $errors->first('name') }}</span>
                            @endif
                        </div>

                        <!-- Email -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Email Address</label>
                            <div class="relative flex items-center">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <input type="email" name="email" value="{{ old('_method') === 'PUT' ? old('email') : '' }}" placeholder="john@example.com" required
                                       class="w-full bg-slate-50 border rounded-lg pl-12 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200">
                            </div>
                            @if($errors->has('email') && old('_method') === 'PUT')
                                <span class="text-rose-600 text-xs mt-1">{{ $errors->first('email') }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Password -->
                            <div class="flex flex-col gap-1.5">
                                <div class="flex justify-between items-center">
                                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Password</label>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-wider">(Optional)</span>
                                </div>
                                <div class="relative flex items-center">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    </div>
                                    <input type="password" name="password" placeholder="Leave blank to keep same"
                                           class="w-full bg-slate-50 border rounded-lg pl-12 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200">
                                </div>
                                @if($errors->has('password') && old('_method') === 'PUT')
                                    <span class="text-rose-600 text-xs mt-1">{{ $errors->first('password') }}</span>
                                @endif
                            </div>

                            <!-- Role -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</label>
                                <div class="relative flex items-center">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </div>
                                    <select name="role" required
                                            class="w-full bg-slate-50 border rounded-lg pl-12 pr-10 py-2.5 text-sm text-slate-900 outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition border-slate-200 appearance-none cursor-pointer">
                                        <option value="agent" {{ (old('_method') === 'PUT' ? old('role') : '') === 'agent' ? 'selected' : '' }}>Agent</option>
                                        <option value="admin" {{ (old('_method') === 'PUT' ? old('role') : '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <!-- Custom Dropdown Arrow -->
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                @if($errors->has('role') && old('_method') === 'PUT')
                                    <span class="text-rose-600 text-xs mt-1">{{ $errors->first('role') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6 justify-end">
                        <button type="button" onclick="closeModal('edit-user-modal')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg py-2.5 px-5 text-sm transition cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-5 text-sm transition cursor-pointer">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 text-xs uppercase tracking-wider bg-slate-50/70">
                            <th class="text-left px-6 py-4 font-semibold">User</th>
                            <th class="text-left px-6 py-4 font-semibold">Email</th>
                            <th class="text-left px-6 py-4 font-semibold">Role</th>
                            <th class="text-left px-6 py-4 font-semibold">Joined</th>
                            <th class="text-right px-6 py-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <!-- Loading Skeleton -->
                    <tbody class="divide-y divide-slate-100" id="table-skeleton">
                        @for($i = 0; $i < 3; $i++)
                            <tr class="animate-pulse">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-100"></div>
                                        <div class="h-4 bg-slate-100 rounded w-24"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-slate-100 rounded w-36"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-5 bg-slate-100 rounded-full w-16"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-slate-100 rounded w-20"></div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <div class="w-8 h-8 bg-slate-100 rounded-lg"></div>
                                        <div class="w-8 h-8 bg-slate-100 rounded-lg"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                    
                    <!-- Actual Content -->
                    <tbody class="divide-y divide-slate-100 hidden" id="table-content">
                        @forelse($users as $u)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-semibold text-sm text-slate-700 flex-shrink-0 bg-slate-100 border border-slate-200">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900 flex items-center gap-2">
                                                {{ $u->name }}
                                                @if($u->id === $user->id)
                                                    <span class="text-xs text-slate-400 font-normal">(you)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $u->email }}</td>
                                <td class="px-6 py-4">
                                    @if($u->role === 'admin')
                                        <span class="bg-indigo-50 text-indigo-700 text-xs px-2.5 py-0.5 rounded border border-indigo-100 font-semibold uppercase tracking-wider">Admin</span>
                                    @else
                                        <span class="bg-emerald-50 text-emerald-700 text-xs px-2.5 py-0.5 rounded border border-emerald-100 font-semibold uppercase tracking-wider">Agent</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500">{{ $u->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit button -->
                                        <button onclick="editUser({{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role]) }})" 
                                                class="text-slate-600 hover:text-slate-950 bg-slate-50 hover:bg-slate-100 border border-slate-200 p-2 rounded-lg transition cursor-pointer flex items-center justify-center"
                                                title="Edit User">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Delete button -->
                                        @if($u->id !== $user->id)
                                            <form method="POST" action="/admin/users/{{ $u->id }}" onsubmit="return confirm('Delete {{ $u->name }}? This cannot be undone.')" class="inline-block m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-700 bg-rose-50/50 hover:bg-rose-50 border border-rose-100 p-2 rounded-lg transition cursor-pointer flex items-center justify-center" title="Delete User">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <span class="w-8 text-center text-slate-400 text-xs flex items-center justify-center">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 bg-slate-50/20">No users found.</td>
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
            
            // Update form action URL and user_id input
            form.action = `/admin/users/${user.id}`;
            modal.querySelector('[name="user_id"]').value = user.id;
            
            // Populate values
            modal.querySelector('[name="name"]').value = user.name;
            modal.querySelector('[name="email"]').value = user.email;
            modal.querySelector('[name="role"]').value = user.role;
            modal.querySelector('[name="password"]').value = '';
            
            openModal('edit-user-modal');
        }

        // Auto-open form if there are validation errors (user was trying to create/edit)
        @if($errors->any())
            @if(old('_method') === 'PUT')
                document.addEventListener('DOMContentLoaded', () => {
                    const userId = "{{ old('user_id') }}";
                    const user = {
                        id: userId,
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

        // Simulate skeleton load transition
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

        function openMobileSidebar() {
            document.getElementById('mobile-sidebar').classList.replace('-translate-x-full', 'translate-x-0');
            document.getElementById('mobile-sidebar-backdrop').classList.remove('opacity-0', 'pointer-events-none');
            document.getElementById('mobile-sidebar-backdrop').classList.add('opacity-100');
        }

        function closeMobileSidebar() {
            document.getElementById('mobile-sidebar').classList.replace('translate-x-0', '-translate-x-full');
            document.getElementById('mobile-sidebar-backdrop').classList.add('opacity-0', 'pointer-events-none');
            document.getElementById('mobile-sidebar-backdrop').classList.remove('opacity-100');
        }

        document.getElementById('mobile-menu-btn').addEventListener('click', openMobileSidebar);
        document.getElementById('mobile-sidebar-close').addEventListener('click', closeMobileSidebar);
        document.getElementById('mobile-sidebar-backdrop').addEventListener('click', closeMobileSidebar);
    </script>
</body>
</html>
