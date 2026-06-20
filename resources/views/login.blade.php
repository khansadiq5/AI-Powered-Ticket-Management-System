<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;850&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="relative min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-50 via-indigo-50/20 to-slate-100 overflow-hidden">
    
    <!-- Background lights -->
    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-200/20 blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-violet-200/20 blur-3xl pointer-events-none"></div>

    <div class="relative sm:mx-auto sm:w-full sm:max-w-md z-10">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-1.5 mb-2">
            <div class="text-xl tracking-wider text-slate-900 flex items-center gap-1">
                <span class="font-extrabold text-slate-950">TICKET</span>
                <span class="font-light text-slate-500">SYSTEM</span>
            </div>
        </div>
        <h2 class="text-center text-2xl font-bold tracking-tight text-slate-950">Welcome Back</h2>
        <p class="mt-1.5 text-center text-sm text-slate-550">
            Sign in to access your administration or agent portal.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 relative z-10">
        <div class="bg-white/80 backdrop-blur-xl px-6 py-8 border border-white/60 rounded-2xl shadow-xl shadow-slate-200/50 sm:px-10">
            @if($errors->any())
                <div class="mb-6 bg-rose-50 border border-rose-200/60 text-rose-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-rose-50 border border-rose-200/60 text-rose-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form class="space-y-5" action="/login" method="POST">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" /></svg>
                        </span>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                               placeholder="you@example.com"
                               class="block w-full rounded-xl border border-slate-200 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 bg-slate-50/50 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all duration-200">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-405">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               placeholder="••••••••"
                               class="block w-full rounded-xl border border-slate-200 pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 bg-slate-50/50 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all duration-200">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="flex w-full justify-center rounded-xl bg-slate-950 hover:bg-slate-900 py-3 px-4 text-sm font-bold text-white shadow-md shadow-slate-950/10 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5 cursor-pointer">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
