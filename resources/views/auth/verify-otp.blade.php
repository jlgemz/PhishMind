<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Verify OTP - PhishMind</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100 text-center">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Check your email</h2>
        <p class="text-slate-500 text-sm mb-6">We've sent a 6-digit verification code to <br><strong>{{ auth()->user()->email }}</strong></p>

        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 p-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
        @endif

        @if(session('otp_hint'))
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-3.5 rounded-xl mb-5 text-xs text-left">
                <div class="font-bold flex items-center gap-1.5 mb-1.5 text-amber-900">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Verification Code (Instant Delivery / Fallback):
                </div>
                <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-amber-300">
                    <span class="text-xs text-slate-600 font-medium">Your 6-digit code:</span>
                    <strong class="text-lg tracking-widest font-mono text-emerald-700 font-bold">{{ session('otp_hint') }}</strong>
                </div>
                <p class="text-[11px] text-amber-700 mt-2">If your email is delayed or filtered into spam, use the code above to verify immediately.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('verify.otp.post') }}">
            @csrf
            <div class="mb-6">
                <input type="text" name="otp" maxlength="6" required value="{{ session('otp_hint') }}" placeholder="000000" class="w-full text-center text-3xl tracking-widest border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition font-mono">
                @error('otp') <span class="text-rose-500 text-xs mt-2 block">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="w-full bg-brand-teal hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition">Verify Account</button>
        </form>
    </div>
</body>
</html>