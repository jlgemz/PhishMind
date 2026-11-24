<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Verify OTP - PhishMind</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100 text-center">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Check your email</h2>
        <p class="text-slate-500 text-sm mb-6">We've sent a 6-digit verification code to <br><strong>{{ auth()->user()->email }}</strong></p>

        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 p-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('verify.otp.post') }}">
            @csrf
            <div class="mb-6">
                <input type="text" name="otp" maxlength="6" required placeholder="000000" autofocus class="w-full text-center text-3xl tracking-widest border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition font-mono">
                @error('otp') <span class="text-rose-500 text-xs mt-2 block">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="w-full bg-brand-teal hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition min-h-[44px]">Verify Account</button>
        </form>

        <div class="text-center mt-6">
            <form method="POST" action="{{ route('verify.otp.resend') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm font-bold text-slate-500 hover:text-brand-teal transition">Didn't get a code? Resend email</button>
            </form>
        </div>
    </div>
</body>
</html>