@extends('layouts.app')

@section('header', 'Analysis Result')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-1">RISK ASSESSMENT COMPLETE</p>
        <h2 class="text-3xl font-bold text-slate-800 mb-1">Understand the result</h2>
        <p class="text-slate-500 text-sm">Random Forest analysis for learning and decision support</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">CONTENT YOU CHECKED</p>
                @php
                    $isUrl = str_starts_with(trim($record->scenario_url), 'http://') || str_starts_with(trim($record->scenario_url), 'https://') || (isset($record->scenario) && $record->scenario->type === 'URL / Web Phishing');
                @endphp
                @if($isUrl)
                    <div class="bg-white border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-700 px-3 py-2 font-mono flex items-center gap-2 shadow-xs">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <div class="overflow-x-auto whitespace-nowrap scrollbar-thin py-0.5 select-all overscroll-x-contain w-full min-w-0">
                            <span class="inline-block font-mono text-slate-700">{{ $record->scenario_url }}</span>
                        </div>
                    </div>
                @else
                    <div class="bg-[#f0f4f8] p-4 rounded-xl text-sm text-slate-700 font-mono leading-relaxed whitespace-pre-line break-words overflow-x-auto">
                        {{ $record->scenario_url }}
                    </div>
                @endif
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex gap-3 flex-wrap items-center">
                @if(!$record->mastery_achieved)
                    <a href="{{ route('learning.retry', $record->id) }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-amber-600 transition flex items-center gap-2">
                        Start Targeted Practice &rarr;
                    </a>
                @endif
                
                @if($record->scenario)
                    <a href="{{ route('learning.next', $record->scenario->id) }}" class="bg-brand-teal text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-teal-700 transition flex items-center gap-2">
                        Next Example &rarr;
                    </a>
                @endif

                <a href="{{ route('profile') }}" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-50 transition">
                    View Learning Profile
                </a>
                
                <a href="{{ route('practice.select') }}" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-50 transition">
                    Select another scenario
                </a>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Verdict with Dynamic Colors -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-sm text-slate-500">Random Forest verdict</p>
                    <svg class="w-5 h-5 {{ $record->model_prediction ? 'text-rose-500' : 'text-emerald-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div class="inline-block px-3 py-1 rounded-md font-bold text-sm mb-6 border
                    {{ $record->model_prediction 
                        ? 'bg-rose-50 text-rose-700 border-rose-100' 
                        : 'bg-emerald-50 text-emerald-700 border-emerald-100' }}">
                    {{ $record->model_prediction ? 'Phishing' : 'Legitimate' }}
                </div>
                
                <div class="flex justify-between items-end mb-2">
                    <p class="text-sm font-semibold text-slate-700">Model Confidence</p>
                    <p class="text-3xl font-bold {{ $record->model_prediction ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ round($record->model_confidence) }}<span class="text-lg ...">%</span>
                    </p>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3 mb-2 flex overflow-hidden">
                    <div class="{{ $record->model_prediction ? 'bg-rose-600' : 'bg-emerald-600' }} h-full rounded-full" style="width: {{ $record->model_confidence }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-slate-400 font-medium mb-6">
                    <span>Low</span>
                    <span>Medium</span>
                    <span>High</span>
                </div>

                <div class="border-t border-slate-100 pt-4">
                    <p class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Your Score</p>
                    @if($record->mastery_achieved)
                        <div class="flex items-center gap-2 text-emerald-600 font-bold text-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Correct Answer
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-rose-600 font-bold text-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Needs Review (Incorrect)
                        </div>
                    @endif
                    <p class="text-xs text-slate-500 mt-2">Your answer: <strong>{{ $record->student_decision ? 'Phishing' : 'Legitimate' }}</strong></p>
                </div>
                
                {{-- NEW: EXPLICITLY CALL OUT MODEL ERRORS --}}
                @if(!$record->mastery_achieved)
                    @if($record->scenario && $record->scenario->is_phishing !== $record->model_prediction)
                        <div class="mt-4 p-4 bg-rose-50 border border-rose-200 rounded-xl">
                            <p class="text-xs font-bold text-rose-800 uppercase mb-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Model Misclassification
                            </p>
                            <p class="text-xs text-rose-700 leading-relaxed">
                                The ground truth for this content is actually <strong>{{ $record->scenario->is_phishing ? 'Phishing' : 'Legitimate' }}</strong>. 
                                The Random Forest model was tricked, which is why it predicted <strong>{{ $record->model_prediction ? 'Phishing' : 'Legitimate' }}</strong>. 
                                Always trust your own verification of the evidence!
                            </p>
                        </div>
                    @endif
                @endif
            </div>

            <!-- SHAP Indicators -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Top Risk Indicators</h3>
                        <p class="text-xs text-slate-500">SHAP contribution to the Random Forest result</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>

                <div class="space-y-5">
                    @if($record->shap_risk_indicators)
                        @foreach($record->shap_risk_indicators as $indicator)
                        <div>
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-slate-700 font-medium">{{ $indicator['feature'] }}</span>
                                <span class="font-bold {{ str_contains($indicator['value'], '-') ? 'text-emerald-600' : 'text-rose-600' }}">{{ $indicator['value'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="{{ str_contains($indicator['value'], '-') ? 'bg-emerald-500' : 'bg-rose-500' }} h-2 rounded-full" style="width: {{ min(100, abs(floatval($indicator['value'])) * 150) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- LLM Explanation -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Plain-language explanation
                </h3>
                <p class="text-xs text-slate-500 mb-4">Grounded in verified security indicators</p>
                <p class="text-sm text-slate-600 leading-relaxed mb-4 whitespace-pre-line">{{ $record->llm_explanation }}</p>
                <p class="text-xs text-slate-400 border-t border-slate-100 pt-4">The plain-language model explains the supplied evidence. It does not decide the verdict.</p>
            </div>

            <!-- Misconception Alert -->
            @if($record->misconceptions && count($record->misconceptions) > 0)
            <div class="bg-[#fef3c7] rounded-2xl border border-amber-200 p-6">
                <h4 class="text-xs font-bold text-amber-700 uppercase mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    POSSIBLE MISCONCEPTION DETECTED
                </h4>
                <p class="text-base font-bold text-slate-800 mb-2">{{ $record->misconceptions[0]['name'] ?? 'Deceptive Indicator Pattern' }}</p>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $record->misconceptions[0]['description'] ?? '' }}</p>
            </div>
            @endif

            <!-- Recommendation -->
            <div class="bg-[#f0fdf4] rounded-2xl border border-emerald-200 p-6">
                <h4 class="text-xs font-bold text-emerald-700 uppercase mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    CYBERSECURITY RECOMMENDATION
                </h4>
                <p class="text-base font-bold text-slate-800 mb-1">What to do next</p>
                @php
                    $recText = null;
                    if (!empty($record->targeted_practice['recommendation'])) {
                        $recText = $record->targeted_practice['recommendation'];
                    } elseif (!empty($record->misconceptions[0]['recommendation'])) {
                        $recText = $record->misconceptions[0]['recommendation'];
                    }
                @endphp
                @if($recText)
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $recText }}</p>
                @elseif($record->scenario && $record->scenario->is_phishing)
                    @php
                        $scType = strtolower($record->scenario->type ?? '');
                    @endphp
                    @if(str_contains($scType, 'sms'))
                        <div class="text-sm text-slate-700 leading-relaxed space-y-1.5">
                            <p>• Never tap on links or short URLs sent in unsolicited text messages.</p>
                            <p>• Never share one-time passcodes (OTP), account passwords, or banking PINs over SMS.</p>
                            <p>• Forward spam or suspicious text messages to your phone carrier spam service (such as 7726) and check your account status directly inside the official mobile app.</p>
                        </div>
                    @elseif(str_contains($scType, 'email'))
                        <div class="text-sm text-slate-700 leading-relaxed space-y-1.5">
                            <p>• Check the sender's full email address and domain name in the message header, not just the displayed name.</p>
                            <p>• Avoid clicking links or opening unexpected attachments from unsolicited or urgent emails.</p>
                            <p>• Forward the suspicious email to your organization's IT security team, and verify any urgent request by calling the sender on a known official number.</p>
                        </div>
                    @else
                        <div class="text-sm text-slate-700 leading-relaxed space-y-1.5">
                            <p>• Carefully examine the full address in your browser address bar for misspelled names or strange extensions (.xyz, .top).</p>
                            <p>• Never enter your login password, payment cards, or sensitive personal details on an unverified web page.</p>
                            <p>• Navigate to trusted websites by typing their known official address directly into the browser address bar or using bookmarks.</p>
                        </div>
                    @endif
                @else
                    @php
                        $scType = strtolower($record->scenario->type ?? '');
                    @endphp
                    @if(str_contains($scType, 'sms'))
                        <p class="text-sm text-slate-700 leading-relaxed">This text notification appears authentic. Remember that genuine banks and universities never request passwords or OTP codes over SMS.</p>
                    @elseif(str_contains($scType, 'email'))
                        <p class="text-sm text-slate-700 leading-relaxed">This email appears authentic. Always verify the sender's full email address if an unexpected action or attachment is requested.</p>
                    @else
                        <p class="text-sm text-slate-700 leading-relaxed">This web address matches official domain patterns. Always confirm the domain name is spelled correctly before submitting credentials.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection