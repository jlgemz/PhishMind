<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PhishMind</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Forgot your password?</h2>
            <p class="text-slate-500 text-sm">Enter your email address and we'll send you a code to reset it.</p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 p-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-800 mb-2">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@university.edu" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm">
                @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="w-full bg-brand-teal hover:bg-teal-700 text-white font-bold py-3.5 rounded-lg transition">
                Send Reset Code &rarr;
            </button>
        </form>
        
        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm font-bold text-slate-500 hover:text-brand-teal">&larr; Back to login</a>
        </div>
    </div>
</body>
</html>