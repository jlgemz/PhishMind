@extends('layouts.app')
@section('header', 'Refresher Feedback')
@section('content')
<div class="max-w-3xl mx-auto">
    
    @if($isCorrect)
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <p class="text-sm font-bold text-emerald-700 uppercase">Correct!</p>
                <p class="text-slate-700 text-sm">You identified this correctly.</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <p class="text-sm font-bold text-rose-700 uppercase">Not quite</p>
                <p class="text-slate-700 text-sm">This was actually <strong>{{ $scenario->is_phishing ? 'Phishing' : 'Legitimate' }}</strong>.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-8 shadow-sm mb-6">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">CONTENT</p>
        @php
            $isUrl = str_starts_with(trim($scenario->content), 'http://') || str_starts_with(trim($scenario->content), 'https://') || $scenario->type === 'URL / Web Phishing';
        @endphp
        @if($isUrl)
            <div class="bg-white border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-700 px-3 py-2 font-mono flex items-center gap-2 shadow-xs mb-6">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <div class="overflow-x-auto whitespace-nowrap scrollbar-thin py-0.5 select-all overscroll-x-contain w-full min-w-0">
                    <span class="inline-block font-mono text-slate-700">{{ $scenario->content }}</span>
                </div>
            </div>
        @else
            <div class="bg-[#f0f4f8] p-4 rounded-xl text-sm text-slate-700 font-mono leading-relaxed whitespace-pre-line break-words mb-6 overflow-x-auto">
                {{ $scenario->content }}
            </div>
        @endif
        
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">WHY</p>
        <p class="text-sm text-slate-700">{{ $scenario->explanation ?? 'No explanation available.' }}</p>
    </div>

    <div class="flex justify-end">
        <a href="{{ route('refresher.start') }}" class="bg-brand-teal text-white px-6 py-3 rounded-lg font-semibold hover:bg-teal-700 transition">
            Next Question &rarr;
        </a>
    </div>
</div>
@endsection