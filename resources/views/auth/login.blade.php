<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - PhishMind</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white flex h-screen overflow-hidden">
    <!-- Left Side: Form -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-8 overflow-y-auto">
        <div class="w-full max-w-[400px]">
            <!-- Logo -->
            <div class="flex items-center gap-2 mb-20">
                <div class="w-8 h-8 bg-brand-dark text-white rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <span class="font-bold text-xl text-slate-900 tracking-tight">PhishMind</span>
            </div>

            <!-- Heading -->
            <p class="text-brand-teal font-bold text-[10px] uppercase tracking-widest mb-2">STUDENT ACCESS</p>
            <h1 class="text-3xl font-bold text-slate-900 mb-3 tracking-tight">Continue your practice</h1>
            <p class="text-slate-500 mb-8 text-sm">Sign in to view your Learning Profile and continue where you left off.</p>

            <!-- Toggle -->
            <div class="flex bg-[#f0f4f8] p-1 rounded-xl mb-8">
                <button class="flex-1 bg-white shadow-sm py-2.5 rounded-lg text-sm font-bold text-slate-800 transition">Log in</button>
                <a href="{{ route('register') }}" class="flex-1 text-center py-2.5 rounded-lg text-sm font-bold text-slate-500 hover:text-slate-800 transition">Sign up</a>
            </div>

            @if(session('success'))
                <div class="bg-emerald-50 text-emerald-700 p-3 rounded-lg mb-6 text-sm border border-emerald-100">{{ session('success') }}</div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Student Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@university.edu" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm">
                    @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-bold text-slate-800">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-bold text-brand-teal hover:underline">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm pr-10">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600" aria-label="Toggle password visibility">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-brand-teal hover:bg-teal-700 text-white font-bold py-3.5 rounded-lg transition flex items-center justify-center gap-2 text-sm shadow-sm">
                    Log in &rarr;
                </button>
            </form>
            
            <p class="text-xs text-center text-slate-400 mt-8 mb-4">Prototype access only. Any non-empty credentials will work.</p>
            <a href="{{ route('home') }}" class="block text-center text-sm font-bold text-brand-teal hover:underline">Return to PhishMind overview</a>
        </div>
    </div>

    <!-- Right Side: Dark Info -->
    <div class="hidden md:flex w-1/2 bg-brand-dark text-white flex-col justify-center p-16 lg:p-24">
        <div class="max-w-md w-full">
            <!-- Shield Icon -->
            <svg class="w-12 h-12 text-brand-teal mb-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            
            <h2 class="text-[2.5rem] font-bold mb-6 leading-[1.15] tracking-tight">Practice the decision, then examine the evidence.</h2>
            <p class="text-[#a1b0c5] text-lg mb-16 leading-relaxed">PhishMind asks students to explain their reasoning before showing the Random Forest verdict and SHAP Risk Indicators.</p>
            
            <hr class="border-slate-700 mb-10">
            
            <ul class="space-y-6 text-sm text-[#a1b0c5]">
                <li class="flex items-center gap-4">
                    <svg class="w-5 h-5 text-brand-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg> 
                    Classify a realistic university scenario
                </li>
                <li class="flex items-center gap-4">
                    <svg class="w-5 h-5 text-brand-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> 
                    Receive grounded, misconception-aware feedback
                </li>
                <li class="flex items-center gap-4">
                    <svg class="w-5 h-5 text-brand-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg> 
                    Track improvement in your Learning Profile
                </li>
            </ul>
        </div>
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