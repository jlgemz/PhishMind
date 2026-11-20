@extends('layouts.app')

@section('header', 'Analysis Result')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-1">RISK ASSESSMENT COMPLETE</p>
        <h2 class="text-3xl font-bold text-slate-800 mb-1">Understand the result</h2>
        <p class="text-slate-500 text-sm">Analysis for the {{ ucfirst($type) }} content you pasted.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">CONTENT YOU CHECKED</p>
                <div class="bg-[#f0f4f8] p-4 rounded-xl text-sm text-slate-700 font-mono leading-relaxed whitespace-pre-line break-words">
                    {{ $content }}
                </div>
            </div>

            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('profile') }}" class="bg-brand-teal text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-teal-700 transition flex items-center gap-2">
                    View Learning Profile &rarr;
                </a>
                <a href="{{ route('check.index') }}" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-slate-50 transition">
                    Check another item
                </a>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Verdict -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-sm text-slate-500">{{ $result['engine'] }} verdict</p>
                    
                    @if($result['verdict'] === 'Phishing')
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    @else
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    @endif
                </div>
                
                <div class="inline-block px-3 py-1 rounded-md font-bold text-sm mb-6 border
                    {{ $result['verdict'] === 'Phishing'
                        ? 'bg-rose-50 text-rose-700 border-rose-100'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-100' }}">
                    {{ $result['verdict'] }}
                </div>

                <div class="flex justify-between items-end mb-2">
                    <p class="text-sm font-semibold text-slate-700">Risk Assessment</p>
                    <p class="text-3xl font-bold {{ $result['verdict'] === 'Phishing' ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $result['risk_score'] }}<span class="text-lg {{ $result['verdict'] === 'Phishing' ? 'text-rose-400' : 'text-emerald-400' }}">%</span>
                    </p>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3 mb-2 flex overflow-hidden">
                    <div class="{{ $result['verdict'] === 'Phishing' ? 'bg-rose-600' : 'bg-emerald-500' }} h-full rounded-full" style="width: {{ $result['risk_score'] }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-slate-400 font-medium">
                    <span>Low</span>
                    <span>Medium</span>
                    <span>High</span>
                </div>
            </div>

            <!-- SHAP indicators -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Top Risk Indicators</h3>
                        <p class="text-xs text-slate-500">Contribution to the AI result</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>

                <div class="space-y-5">
                    @foreach($result['indicators'] as $indicator)
                        <div>
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-slate-700 font-medium">{{ $indicator['feature'] }}</span>
                                <span class="font-bold {{ str_contains($indicator['value'], '-') ? 'text-emerald-600' : 'text-rose-600' }}">{{ $indicator['value'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="{{ str_contains($indicator['value'], '-') ? 'bg-emerald-500' : 'bg-rose-500' }} h-2 rounded-full"
                                     style="width: {{ min(100, abs(floatval($indicator['value'])) * 150) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Explanation -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Plain-language explanation
                </h3>
                <p class="text-xs text-slate-500 mb-4">AI-generated · grounded in verified evidence</p>
                <p class="text-sm text-slate-600 leading-relaxed mb-4">{{ $result['explanation'] }}</p>
            </div>

            <!-- Misconception (optional) -->
            @if(!empty($result['misconception']))
                <div class="bg-[#fef3c7] rounded-2xl border border-amber-200 p-6">
                    <h4 class="text-xs font-bold text-amber-700 uppercase mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        POSSIBLE MISCONCEPTION DETECTED
                    </h4>
                    <p class="text-base font-bold text-slate-800 mb-2">{{ $result['misconception'] }}</p>
                </div>
            @endif

            <!-- Recommendation -->
            <div class="bg-[#f0fdf4] rounded-2xl border border-emerald-200 p-6">
                <h4 class="text-xs font-bold text-emerald-700 uppercase mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    CYBERSECURITY RECOMMENDATION
                </h4>
                <p class="text-base font-bold text-slate-800 mb-1">What to do next</p>
                <p class="text-sm text-slate-700">{{ $result['recommendation'] }}</p>
            </div>
        </div>
    </div>
</div>
@endsection