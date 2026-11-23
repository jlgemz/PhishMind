<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Register - PhishMind</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white flex h-screen overflow-hidden">
    <!-- Left Side: Form -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-8 overflow-y-auto">
        <div class="w-full max-w-[400px]">
            <!-- Logo -->
            <div class="flex items-center gap-2 mb-16">
                <div class="w-8 h-8 bg-brand-dark text-white rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <span class="font-bold text-xl text-slate-900 tracking-tight">PhishMind</span>
            </div>

            <!-- Heading -->
            <p class="text-brand-teal font-bold text-[10px] uppercase tracking-widest mb-2">STUDENT ACCESS</p>
            <h1 class="text-3xl font-bold text-slate-900 mb-3 tracking-tight">Create your student account</h1>
            <p class="text-slate-500 mb-8 text-sm">Use your student email to begin the prototype.</p>

            <!-- Toggle -->
            <div class="flex bg-[#f0f4f8] p-1 rounded-xl mb-8">
                <a href="{{ route('login') }}" class="flex-1 text-center py-2.5 rounded-lg text-sm font-bold text-slate-500 hover:text-slate-800 transition">Log in</a>
                <button class="flex-1 bg-white shadow-sm py-2.5 rounded-lg text-sm font-bold text-slate-800 transition">Sign up</button>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('register.post') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm">
                    @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Student Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@university.edu" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm">
                    @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm pr-10">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600" aria-label="Toggle password visibility">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                    @error('password') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Confirm Password</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition text-slate-800 text-sm pr-10">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600" aria-label="Toggle password visibility">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Terms & Conditions Checkbox -->
                <div class="mb-6">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" name="terms" id="terms-checkbox" value="1" required class="mt-1 w-4 h-4 text-brand-teal rounded border-slate-300 focus:ring-brand-teal cursor-pointer">
                        <span class="text-xs text-slate-600 leading-relaxed">
                            I have read and agree to the <button type="button" id="open-terms-modal" class="text-brand-teal font-bold hover:underline">Terms &amp; Conditions</button> and Academic Research Policy.
                        </span>
                    </label>
                    @error('terms') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" id="submit-register-btn" disabled class="w-full bg-brand-teal hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-lg transition flex items-center justify-center gap-2 text-sm shadow-sm">
                    Create account &rarr;
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

    <!-- Terms and Conditions Modal -->
    <div id="terms-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 sm:p-8 shadow-2xl max-h-[85vh] flex flex-col border border-slate-200">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-teal-50 text-brand-teal flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Terms &amp; Conditions</h3>
                        <p class="text-xs text-slate-400">PhishMind Adaptive Cybersecurity Training</p>
                    </div>
                </div>
                <button type="button" id="close-terms-modal-x" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="overflow-y-auto py-5 space-y-4 text-xs text-slate-600 leading-relaxed pr-2">
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1">1. Educational &amp; Academic Purpose</h4>
                    <p>PhishMind is an interactive cybersecurity training and research platform developed to assess and enhance user resilience against digital threats (phishing URLs, emails, and SMS). All scenarios, simulation metrics, and synthetic datasets are strictly designated for educational and academic research evaluation.</p>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1">2. Acceptable Use Policy</h4>
                    <p>Users agree to interact with the platform ethically. You may not attempt to reverse engineer malicious patterns for non-defensive deployment, submit real confidential passwords or actual private cryptographic keys, or use the system for unauthorized penetration testing against third parties.</p>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1">3. Automated AI &amp; Machine Learning Advisory</h4>
                    <p>Automated classifications generated by the Random Forest model and plain-language explanations are analytical indicators designed to assist human judgment. They do not constitute an absolute cryptographic or legal guarantee of a communication's legitimacy.</p>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1">4. Data Protection &amp; Learning Analytics</h4>
                    <p>Your practice responses, reasoning explanations, and diagnostic performance are recorded securely to adapt instructional exercises to your learning needs. Data is treated in accordance with applicable academic research ethics and privacy standards.</p>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" id="close-terms-modal-btn" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                    Close
                </button>
                <button type="button" id="accept-terms-modal-btn" class="px-5 py-2 rounded-lg bg-brand-teal hover:bg-teal-700 text-white font-bold text-xs transition shadow-sm">
                    I Understand &amp; Accept
                </button>
            </div>
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

    const termsCheckbox = document.getElementById('terms-checkbox');
    const submitBtn = document.getElementById('submit-register-btn');
    const termsModal = document.getElementById('terms-modal');
    const openTermsBtn = document.getElementById('open-terms-modal');
    const closeTermsX = document.getElementById('close-terms-modal-x');
    const closeTermsBtn = document.getElementById('close-terms-modal-btn');
    const acceptTermsBtn = document.getElementById('accept-terms-modal-btn');

    function syncSubmitState() {
        if (!termsCheckbox || !submitBtn) return;
        if (termsCheckbox.checked) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    if (termsCheckbox) {
        termsCheckbox.addEventListener('change', syncSubmitState);
        syncSubmitState();
    }

    function showTermsModal() {
        if (!termsModal) return;
        termsModal.classList.remove('hidden');
        termsModal.classList.add('flex');
    }

    function hideTermsModal() {
        if (!termsModal) return;
        termsModal.classList.add('hidden');
        termsModal.classList.remove('flex');
    }

    if (openTermsBtn) openTermsBtn.addEventListener('click', showTermsModal);
    if (closeTermsX) closeTermsX.addEventListener('click', hideTermsModal);
    if (closeTermsBtn) closeTermsBtn.addEventListener('click', hideTermsModal);

    if (acceptTermsBtn) {
        acceptTermsBtn.addEventListener('click', function () {
            if (termsCheckbox) {
                termsCheckbox.checked = true;
                syncSubmitState();
            }
            hideTermsModal();
        });
    }

    if (termsModal) {
        termsModal.addEventListener('click', function (e) {
            if (e.target === termsModal) {
                hideTermsModal();
            }
        });
    }
    </script>
</body>
</html>