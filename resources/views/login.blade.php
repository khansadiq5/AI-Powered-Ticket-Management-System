<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-950 text-slate-100 min-h-screen flex flex-col justify-center items-center p-6">

    <div class="bg-slate-900/50 backdrop-blur-lg border border-white/5 rounded-2xl p-8 md:p-10 w-full max-w-md shadow-2xl flex flex-col gap-8">

        <div class="text-center flex flex-col gap-2">
            <div class="text-2xl font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">TICKET MANAGEMENT SYSTEM</div>
            <h1 class="text-xl font-semibold text-white">Sign In</h1>
            <p class="text-xs text-slate-400">Access your ticketing panel</p>
        </div>

        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 text-red-200 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-200 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="/login" class="flex flex-col gap-6">
            @csrf

            <div class="flex flex-col gap-2">
                <label for="email" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="username" required
                       class="w-full bg-slate-950/60 border border-white/5 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-500 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
            </div>

            <div class="flex flex-col gap-2">
                <label for="password" class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required
                       class="w-full bg-slate-950/60 border border-white/5 rounded-lg px-4 py-3 text-sm text-slate-200 placeholder-slate-500 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg py-3 px-4 text-sm transition cursor-pointer">
                Sign In
            </button>
        </form>
    </div>

</body>
</html>
