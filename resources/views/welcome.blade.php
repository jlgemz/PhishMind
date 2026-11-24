<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>PhishMind - Phishing Awareness</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased">
    <!-- Navbar -->
    <nav class="bg-[#f8fafc] py-5">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('logo.svg') }}" alt="PhishMind Logo" class="w-8 h-8 rounded-lg object-contain">
                <span class="text-xl font-bold text-slate-900 tracking-tight">PhishMind</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#how-it-works" class="hover:text-slate-900 transition">How it Works</a>
                <a href="#features" class="hover:text-slate-900 transition">Features</a>
                <a href="#scenarios" class="hover:text-slate-900 transition">Scenarios</a>
                <a href="#faq" class="hover:text-slate-900 transition">FAQ</a>
                <a href="{{ route('login') }}" class="bg-brand-teal text-white px-5 py-2 rounded-lg hover:bg-teal-700 transition">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-[#f8fafc] pt-16 pb-24 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
            <div>
                <p class="text-brand-teal font-bold text-[11px] uppercase tracking-widest mb-4">EXPLAINABLE PHISHING AWARENESS FOR STUDENTS</p>
                <h1 class="text-[3.5rem] font-bold text-slate-900 leading-[1.1] mb-6">Learn why it's phishing, not just that it is.</h1>
                <p class="text-lg text-slate-600 mb-8 max-w-md">PhishMind helps university students detect phishing, understand the warning signs, and correct the thinking behind their mistakes.</p>
                <div class="flex gap-3">
                    <a href="{{ route('register') }}" class="bg-brand-teal text-white px-6 py-3 rounded-lg font-semibold hover:bg-teal-700 transition text-sm">Start Practicing &rarr;</a>
                    <a href="{{ route('login') }}" class="bg-white border border-slate-300 text-slate-700 px-6 py-3 rounded-lg font-semibold hover:bg-slate-50 transition text-sm">Check Suspicious Content</a>
                </div>
                <p class="text-[11px] text-slate-500 mt-6">A thesis prototype using mock scenarios and results. No real machine learning is performed.</p>
            </div>
            
            <!-- Mock SHAP UI Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 relative">
                <div class="flex justify-between items-start mb-6">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ANALYSIS RESULT</p>
                    <span class="bg-rose-100 text-rose-700 text-xs font-bold px-2 py-0.5 rounded">High risk</span>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">Phishing</h3>
                <p class="text-xs text-slate-500 mb-4">Risk Assessment</p>
                
                <div class="flex justify-between items-end mb-1">
                    <p class="text-sm font-bold text-slate-800">High risk</p>
                    <p class="text-xl font-bold text-rose-600">86</p>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 mb-6">
                    <div class="bg-rose-600 h-2 rounded-full" style="width: 86%"></div>
                </div>
                
                <p class="text-xs font-semibold text-slate-700 mb-1">Top Risk Indicators</p>
                <p class="text-[10px] text-slate-400 mb-4">SHAP contribution to the Random Forest result</p>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <div class="flex justify-between text-[11px] text-slate-600 mb-1"><span>Domain differs from official university domain</span><span class="text-rose-600">+0.34</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5"><div class="bg-rose-500 h-1.5 rounded-full" style="width: 80%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[11px] text-slate-600 mb-1"><span>Credential request</span><span class="text-rose-600">+0.25</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5"><div class="bg-rose-500 h-1.5 rounded-full" style="width: 60%"></div></div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[11px] text-slate-600 mb-1"><span>Urgent account-expiry language</span><span class="text-rose-600">+0.18</span></div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5"><div class="bg-rose-500 h-1.5 rounded-full" style="width: 40%"></div></div>
                    </div>
                </div>
                <div class="bg-[#fef3c7] border border-amber-200 rounded-md p-3 text-xs">
                    <strong class="block text-amber-800 text-[10px] mb-1">POSSIBLE MISCONCEPTION</strong>
                    <span class="text-slate-800">HTTPS means the site is safe.</span>
                </div>
            </div>
        </div>
    </section>

    <!-- The Problem Section -->
    <section class="bg-[#f8fafc] py-20">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 items-start">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">THE PROBLEM</p>
                <h2 class="text-2xl font-bold text-slate-900 leading-tight">Familiar context can hide unsafe requests.</h2>
            </div>
            <div class="md:col-span-2">
                <p class="text-slate-600 text-sm leading-relaxed max-w-3xl">Phishing messages imitate familiar university activities such as enrollment, grade release, scholarships, and portal password resets. Knowing whether an answer is right or wrong does not reveal the reasoning behind it, so the same misconception can shape the next decision.</p>
            </div>
        </div>
    </section>

    <!-- What Makes Different Section -->
    <section class="bg-brand-dark py-24 text-white">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-16">
            <div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    WHAT MAKES PHISHMIND DIFFERENT
                </p>
                <h2 class="text-3xl font-bold leading-tight">It examines the decision and the thinking behind it.</h2>
            </div>
            <div>
                <p class="text-[#a1b0c5] text-sm mb-6 leading-relaxed max-w-lg">PhishMind does not only detect phishing or explain why something looks suspicious. Students classify an example and write their reasoning, so the system can identify a possible misconception and give a targeted correction and related practice.</p>
                <div class="flex flex-wrap gap-2 text-[10px] text-slate-300">
                    <span class="px-2 py-1 bg-[#1e293b] rounded border border-slate-700">Student reasoning</span>
                    <span class="px-2 py-1 bg-[#1e293b] rounded border border-slate-700">Verified evidence</span>
                    <span class="px-2 py-1 bg-[#1e293b] rounded border border-slate-700">Targeted correction</span>
                    <span class="px-2 py-1 bg-[#1e293b] rounded border border-slate-700">Related practice</span>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="bg-white py-24">
        <div class="max-w-7xl mx-auto px-6">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">HOW IT WORKS</p>
            <h2 class="text-3xl font-bold text-slate-900 mb-3">From decision to adaptive practice</h2>
            <p class="text-sm text-slate-500 mb-16 max-w-2xl">Each stage has a distinct role. The language model explains verified evidence; it does not make the classification.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 relative">
                <!-- Connecting Line -->
                <div class="hidden md:block absolute top-[11px] left-8 right-8 h-[1px] bg-slate-200 z-0"></div>

                @foreach(['Student examines an example' => 'A phishing or legitimate URL scenario is presented.', 'Student decides and explains' => 'They classify it and write the reasoning behind the choice.', 'Evidence is identified' => 'Random Forest classifies; SHAP identifies contributing factors.', 'Evidence is explained' => 'Qwen 3.5 2B explains verified evidence in plain language.', 'Learning adapts' => 'Targeted feedback, a retry, and the Learning Profile support improvement.'] as $title => $desc)
                <div class="relative z-10">
                    <div class="w-6 h-6 bg-brand-teal rounded-full border-4 border-white mb-6"></div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">STEP {{ $loop->iteration }}</p>
                    <h4 class="font-bold text-slate-900 text-sm mb-2">{{ $title }}</h4>
                    <p class="text-[11px] text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="bg-[#f0f4f8] py-24 border-t border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6">
            <p class="text-[10px] font-bold text-brand-teal uppercase tracking-wider mb-3">FEATURES</p>
            <h2 class="text-3xl font-bold text-slate-900 mb-8">Evidence first, explanation second</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Large Dark Card -->
                <div class="md:col-span-2 bg-brand-dark rounded-2xl p-8 text-white flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold mb-3 flex items-start gap-2">
                            <svg class="w-5 h-5 text-brand-teal mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Random Forest detection with SHAP Risk Indicators
                        </h3>
                        <p class="text-[13px] text-[#a1b0c5] mb-8 max-w-lg leading-relaxed">Random Forest classifies each mock input. SHAP then identifies which verified features contributed to that result, keeping the evidence visible and inspectable.</p>
                    </div>
                    <div class="flex items-end gap-1.5 h-16">
                        <div class="w-8 h-8 bg-[#115e59] rounded-t-sm"></div>
                        <div class="w-8 h-14 bg-[#0d9488] rounded-t-sm"></div>
                        <div class="w-8 h-10 bg-[#14b8a6] rounded-t-sm"></div>
                        <div class="w-8 h-4 bg-[#5eead4] rounded-t-sm"></div>
                        <div class="w-8 h-6 bg-[#2dd4bf] rounded-t-sm"></div>
                    </div>
                </div>
                <!-- Small White Card -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-bold mb-3 flex items-center gap-2 text-slate-900">
                        <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Risk Assessment
                    </h3>
                    <p class="text-[13px] text-slate-500 leading-relaxed">A Low, Medium, or High level summarizes the mock model result without presenting it as certainty.</p>
                </div>
            </div>
            
            <!-- Bottom 4 Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach(['Grounded explanations' => 'Qwen 3.5 2B converts verified evidence into readable guidance and never classifies.', 'Misconception-Aware Adaptive Feedback' => 'Student reasoning informs a focused correction and related retry.', 'Cybersecurity recommendations' => 'Every result ends with a practical, context-aware next step.', 'Learning Profile' => 'Progress, active misconceptions, resolved misconceptions, and attempts remain visible.'] as $title => $desc)
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col gap-4">
                    <div class="w-8 h-8 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm mb-2">{{ $title }}</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Supported Scenarios -->
    <section id="scenarios" class="bg-white py-24">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-16">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">SUPPORTED SCENARIOS</p>
                <h2 class="text-2xl font-bold text-slate-900 mb-3 leading-tight">Practice with university situations that feel familiar.</h2>
                <p class="text-slate-500 text-[13px] leading-relaxed">Each example uses realistic academic context without claiming to reproduce a real incident.</p>
            </div>
            <div class="md:col-span-2">
                <div class="border border-slate-200 rounded-xl overflow-hidden bg-[#f8fafc]">
                    @foreach(['URL / Web Phishing' => 'portal.university-security.net/account/verify', 'Email Phishing' => 'Action required: Confirm your scholarship by 5 PM', 'Messages / Smishing' => 'Enrollment on hold. Review via sms-enrollment'] as $type => $content)
                    <div class="flex items-center gap-4 p-4 border-b border-slate-200 last:border-0 bg-white hover:bg-slate-50 transition">
                        <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center shrink-0 border border-slate-200">
                            @if($loop->iteration == 1)
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            @elseif($loop->iteration == 2)
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            @else
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 flex justify-between items-center text-sm border-b border-slate-100 pb-2 mb-[-8px] mt-2">
                            <span class="font-bold text-slate-800 text-xs">{{ $type }}</span>
                            <span class="text-slate-500 text-[11px]">{{ $content }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Who it's for & Scope -->
    <section class="bg-[#e2e8f0] py-20 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    WHO IT'S FOR
                </p>
                <h2 class="text-2xl font-bold text-slate-900 mb-2 leading-tight">Designed primarily for university students building safer decision habits.</h2>
            </div>
            <div class="border-l border-slate-300 pl-8">
                <h4 class="font-bold text-slate-900 mb-2 flex items-center gap-2 text-sm"><svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg> Educators</h4>
                <p class="text-xs text-slate-600 leading-relaxed">Support scenario-based awareness activities and review learning patterns.</p>
            </div>
            <div class="border-l border-slate-300 pl-8">
                <h4 class="font-bold text-slate-900 mb-2 flex items-center gap-2 text-sm"><svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg> Researchers</h4>
                <p class="text-xs text-slate-600 leading-relaxed">Study explainability, misconceptions, and adaptive feedback in this context.</p>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-[#fef3c7] border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1 flex items-center gap-2">Honest scope</h4>
                    <p class="text-xs text-slate-700 leading-relaxed">PhishMind is an awareness and decision-support tool that does not replace institutional security controls. It does not cover QR phishing, voice phishing, malware analysis, or attachment analysis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ & CTA -->
    <section id="faq" class="bg-white py-24 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-16">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">FAQ</p>
                <h2 class="text-3xl font-bold text-slate-900 mb-4 leading-tight">Questions about the system</h2>
            </div>
            <div class="md:col-span-2 space-y-1">
                @foreach(['What is phishing?' => 'Phishing is a social engineering attack where attackers impersonate a trusted entity to steal credentials or sensitive information.', 'Does the AI decide what is phishing?' => 'No. Random Forest classifies the input. SHAP identifies contributing factors. Qwen 3.5 2B only turns verified evidence into a plain-language explanation; it never decides whether an input is phishing.', 'What is a misconception here?' => 'A misconception is a flawed reasoning pattern that leads to an incorrect decision, such as believing HTTPS alone guarantees safety.', 'Who can use it?' => 'University students, educators, and researchers.', 'What does it not cover?' => 'QR phishing, voice phishing, malware analysis, and attachment analysis.'] as $q => $a)
                <div x-data="{ open: false }" class="border-b border-slate-200 py-5">
                    <button @click="open = !open" class="w-full flex justify-between items-center font-bold text-slate-800 text-sm text-left hover:text-brand-teal transition">
                        {{ $q }}
                        <svg :class="{'rotate-180': open}" class="w-5 h-5 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" x-collapse class="text-sm text-slate-500 mt-3 leading-relaxed">{{ $a }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Footer CTA -->
    <section class="bg-brand-dark py-16">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h2 class="text-2xl font-bold mb-2 text-white">Ready to practice?</h2>
                <p class="text-slate-400 text-sm">Examine a realistic university scenario and explain your decision.</p>
            </div>
            <a href="{{ route('register') }}" class="bg-white text-brand-dark px-6 py-3 rounded-lg font-bold text-sm hover:bg-slate-100 transition shrink-0">Start Practicing &rarr;</a>
        </div>
    </section>
    
    <!-- Simple footer -->
    <footer class="bg-[#0b1120] border-t border-slate-800 py-6 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-white rounded flex items-center justify-center">
                    <svg class="w-4 h-4 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <span class="font-bold text-slate-300">PhishMind</span>
            </div>
            <span>A thesis project. (University Name)</span>
        </div>
    </footer>
</body>
</html>