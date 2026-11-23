@extends('layouts.app')

@section('header', 'Analysis Result')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                {{ $type === 'url' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                   ($type === 'email' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                   'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                @if($type === 'url')
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    URL Modality
                @elseif($type === 'email')
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    Email Modality
                @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    SMS Modality
                @endif
            </span>
            <span class="text-xs text-slate-400 font-medium">Machine Learning Inference</span>
        </div>
        <h2 class="text-3xl font-bold text-slate-800 mb-1">Random Forest Risk Assessment</h2>
        <p class="text-slate-500 text-sm">Evaluating learned structural and lexical patterns for the submitted {{ strtoupper($type) }} input.</p>
    </div>

    <!-- Prominent Warning Banner -->
    @if(!empty($result['warning']))
        <div class="mb-6 rounded-2xl border p-4 sm:p-5 flex items-start gap-3
            {{ $result['verdict'] === 'Invalid Input' ? 'bg-amber-50 border-amber-200 text-amber-900' :
               ($result['verdict'] === 'Phishing' ? 'bg-rose-50 border-rose-200 text-rose-900' :
               ($result['is_borderline'] ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-slate-50 border-slate-300 text-slate-800')) }}">
            <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $result['verdict'] === 'Phishing' ? 'text-rose-600' : ($result['verdict'] === 'Invalid Input' || $result['is_borderline'] ? 'text-amber-600' : 'text-slate-500') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider mb-1">
                    {{ $result['verdict'] === 'Invalid Input' ? 'Input Validation Warning' :
                       ($result['is_borderline'] ? 'Uncertain / Borderline Prediction Warning' :
                       ($result['verdict'] === 'Phishing' ? 'Threat Advisory' : 'Model Limitation & Security Notice')) }}
                </h4>
                <p class="text-xs sm:text-sm leading-relaxed">{{ $result['warning'] }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column: Submitted Content -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">SUBMITTED INPUT</p>
                    @if(!empty($result['is_dataset_match']))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            Scenario #{{ $result['scenario_id'] }} (Known Benchmark)
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                            Unseen Input
                        </span>
                    @endif
                </div>

                @php
                    $isUrl = ($type === 'url') || str_starts_with(trim($content), 'http://') || str_starts_with(trim($content), 'https://');
                @endphp
                @if($isUrl)
                    <div class="bg-white border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-700 px-3 py-2 font-mono flex items-center gap-2 shadow-xs">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <div class="overflow-x-auto whitespace-nowrap scrollbar-thin py-0.5 select-all overscroll-x-contain w-full min-w-0">
                            <span class="inline-block font-mono text-slate-700">{{ $content }}</span>
                        </div>
                    </div>
                @else
                    <div class="bg-[#f0f4f8] p-4 rounded-xl text-sm text-slate-700 font-mono leading-relaxed whitespace-pre-line break-words overflow-x-auto">
                        {{ $content }}
                    </div>
                @endif
            </div>

            <!-- Model Details & Modality Info -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm text-xs text-slate-600 space-y-3">
                <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    Classifier Architecture &amp; Methodology
                </h4>
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                    <div>
                        <span class="text-slate-400 block">Inference Engine:</span>
                        <span class="font-semibold text-slate-700">{{ $result['engine'] }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Modality:</span>
                        <span class="font-semibold text-slate-700">{{ ucfirst($type) }} Analysis</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Evaluation Mode:</span>
                        <span class="font-semibold text-slate-700">Feature Extraction (39 Features)</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Attribution Method:</span>
                        <span class="font-semibold text-slate-700">SHAP TreeExplainer</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('check.index') }}" class="bg-brand-teal text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-teal-700 transition flex items-center gap-2">
                    &larr; Check another input
                </a>
                <a href="{{ route('practice.select') }}" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-50 transition">
                    Practice Scenarios
                </a>
            </div>
        </div>

        <!-- Right Column: Results & SHAP -->
        <div class="space-y-6">
            <!-- Classification Verdict Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-sm font-semibold text-slate-500">Predicted Classification</p>
                    
                    @if($result['verdict'] === 'Phishing')
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            High Threat Signal
                        </span>
                    @elseif($result['verdict'] === 'Legitimate')
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Clean Pattern Signal
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Format Unprocessable
                        </span>
                    @endif
                </div>
                
                <div class="inline-block px-3.5 py-1.5 rounded-lg font-bold text-base mb-6 border
                    {{ $result['verdict'] === 'Phishing'
                        ? 'bg-rose-50 text-rose-700 border-rose-200'
                        : ($result['verdict'] === 'Legitimate'
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                    {{ $result['verdict'] }}
                </div>

                @if($result['verdict'] !== 'Invalid Input')
                    <div class="flex justify-between items-end mb-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Threat Risk Score</p>
                            <p class="text-xs text-slate-400">Model Confidence: {{ round($result['confidence'] * 100, 1) }}%</p>
                        </div>
                        <p class="text-3xl font-bold {{ $result['verdict'] === 'Phishing' ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ $result['risk_score'] }}<span class="text-lg {{ $result['verdict'] === 'Phishing' ? 'text-rose-400' : 'text-emerald-400' }}">%</span>
                        </p>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 mb-2 flex overflow-hidden">
                        <div class="{{ $result['verdict'] === 'Phishing' ? 'bg-rose-600' : 'bg-emerald-500' }} h-full rounded-full transition-all duration-500" style="width: {{ $result['risk_score'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-400 font-medium">
                        <span>Low Threat (0%)</span>
                        <span>Ambiguous (50%)</span>
                        <span>Critical Threat (100%)</span>
                    </div>

                    <!-- Class Probabilities Breakdown -->
                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                        <span>P(Legitimate): <strong class="text-slate-800">{{ round(($result['probabilities']['legitimate'] ?? 0) * 100, 1) }}%</strong></span>
                        <span>P(Phishing): <strong class="text-slate-800">{{ round(($result['probabilities']['phishing'] ?? 0) * 100, 1) }}%</strong></span>
                        <span>Reliability: <strong class="{{ $result['reliability'] === 'high' ? 'text-emerald-600' : ($result['reliability'] === 'low' ? 'text-amber-600' : 'text-blue-600') }}">{{ ucfirst($result['reliability'] ?? 'moderate') }}</strong></span>
                    </div>
                @endif
            </div>

            <!-- Detection Indicators (SHAP Feature Attributions) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-5">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Detection Indicators &amp; Extracted Features</h3>
                        <p class="text-xs text-slate-500">Feature contributions to the model's prediction</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>

                <div class="space-y-4">
                    @foreach($result['indicators'] as $indicator)
                        @php
                            $isSafety = ($indicator['type'] ?? '') === 'safety' || str_starts_with($indicator['value'], '-');
                            $isError  = ($indicator['type'] ?? '') === 'error';
                        @endphp
                        <div class="bg-slate-50/70 rounded-xl p-3 border border-slate-100">
                            <div class="flex justify-between items-center text-xs sm:text-sm mb-1.5">
                                <span class="text-slate-700 font-medium flex items-center gap-1.5">
                                    @if($isError)
                                        <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                                    @elseif($isSafety)
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                                    @endif
                                    {{ $indicator['feature'] }}
                                </span>
                                <span class="font-bold font-mono text-xs px-2 py-0.5 rounded
                                    {{ $isError ? 'bg-amber-100 text-amber-800' :
                                       ($isSafety ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ $indicator['value'] }} {{ $isSafety ? '(Safety)' : ($isError ? '(Error)' : '(Risk)') }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5">
                                <div class="{{ $isError ? 'bg-amber-500' : ($isSafety ? 'bg-emerald-500' : 'bg-rose-500') }} h-1.5 rounded-full"
                                     style="width: {{ min(100, max(10, abs(floatval($indicator['value'])) * 150)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Plain-Language Explanation -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Analysis Explanation
                </h3>
                <p class="text-xs text-slate-500 mb-3">Grounded in the Random Forest model's feature contributions</p>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $result['explanation'] }}</p>
            </div>

            <!-- Cybersecurity Recommendation -->
            <div class="rounded-2xl border p-6
                {{ $result['verdict'] === 'Phishing' ? 'bg-rose-50/70 border-rose-200' :
                   ($result['verdict'] === 'Invalid Input' ? 'bg-amber-50/70 border-amber-200' : 'bg-emerald-50/70 border-emerald-200') }}">
                <h4 class="text-xs font-bold uppercase tracking-wider mb-2 flex items-center gap-2
                    {{ $result['verdict'] === 'Phishing' ? 'text-rose-700' :
                       ($result['verdict'] === 'Invalid Input' ? 'text-amber-700' : 'text-emerald-700') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    RECOMMENDED SECURITY ACTION
                </h4>
                <p class="text-sm text-slate-800 font-medium leading-relaxed whitespace-pre-line">{{ $result['recommendation'] }}</p>
            </div>
        </div>
    </div>
</div>
@endsection