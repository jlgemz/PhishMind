@extends('layouts.app')
@section('header', 'Practice')
@section('content')

<!-- PERSONALIZED REFRESHER BANNER -->
@if($refresherDue)
<div class="bg-brand-dark rounded-2xl p-5 sm:p-6 text-white mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-teal rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </div>
        <div>
            <p class="text-xs text-teal-300 font-bold uppercase tracking-wider mb-1">Time for a refresher</p>
            <h3 class="text-base sm:text-lg font-bold mb-1">Your personalized review is ready</h3>
            <p class="text-xs sm:text-sm text-slate-300">5 scenarios · about 5 minutes · weighted toward your weakest indicator</p>
        </div>
    </div>
    <a href="{{ route('refresher.start') }}" class="w-full sm:w-auto text-center justify-center bg-brand-teal hover:bg-teal-700 text-white px-6 py-3 rounded-xl font-bold text-sm transition flex items-center gap-2 shrink-0 min-h-[44px]">
        Start Refresher &rarr;
    </a>
</div>
@endif

<div class="mb-10">
    <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-2">SCENARIO SELECTION</p>
    <h2 class="text-3xl font-bold text-slate-800 mb-2">Choose what to practice</h2>
    <p class="text-slate-600">Each scenario asks you to classify an item and explain the evidence behind your choice.</p>
</div>

<!-- URL / Web -->
<div class="mb-12">
    <div class="flex justify-between items-end border-b border-slate-200 pb-2 mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">URL / Web Phishing Detection</h3>
            <p class="text-sm text-slate-500">Inspect web addresses and page context.</p>
        </div>
        <span class="text-sm text-slate-500">{{ $urlScenario ? '1 scenario available' : 'No scenarios' }}</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($urlScenario)
        <a href="{{ route('learning.start', $urlScenario->id) }}" class="block bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                </div>
                <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </div>
            <h4 class="font-bold text-slate-800 mb-1">URL/Web Phishing</h4>
            <p class="text-sm text-slate-500 mb-6">Examine domains, page context, and credential requests.</p>
        </a>
        @endif
    </div>
</div>

<!-- Message and Social Engineering Detection (Email, SMS ONLY) -->
<div>
    <div class="flex justify-between items-end border-b border-slate-200 pb-2 mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Message and Social Engineering Detection</h3>
            <p class="text-sm text-slate-500">Evaluate senders, requests, timing, and destination links.</p>
        </div>
        <span class="text-sm text-slate-500">
            {{ ($emailScenario?1:0) + ($smsScenario?1:0) }} scenarios available
        </span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($emailScenario)
        <a href="{{ route('learning.start', $emailScenario->id) }}" class="block bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </div>
            <h4 class="font-bold text-slate-800 mb-1">Email Phishing</h4>
            <p class="text-sm text-slate-500 mb-6">Assess senders, requests, links, and institutional context.</p>
        </a>
        @endif

        @if($smsScenario)
        <a href="{{ route('learning.start', $smsScenario->id) }}" class="block bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <svg class="w-5 h-5 text-brand-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </div>
            <h4 class="font-bold text-slate-800 mb-1">Messages Phishing</h4>
            <p class="text-sm text-slate-500 mb-6">Spot message lures: prize claims, delivery notices, fake bank alerts.</p>
        </a>
        @endif
    </div>
</div>
@endsection