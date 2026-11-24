<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PhishMind</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Create new password</h2>
            <p class="text-slate-500 text-sm">Your new password must be different from previous used passwords.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-800 mb-2">New Password</label>
                <div class="relative">
                    <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm pr-10">
                    <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                </div>
                @error('password') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-800 mb-2">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm pr-10">
                    <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-teal hover:bg-teal-700 text-white font-bold py-3.5 rounded-lg transition">
                Reset Password
            </button>
        </form>
    </div>

    <script>
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = this.parentElement.querySelector('input');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
    </script>
</body>
</html>