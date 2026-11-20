@extends('layouts.app')

@section('header', 'Dashboard')

@section('content')
<div class="mb-8 flex justify-between items-end">
    <div>
        <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-1">WELCOME BACK</p>
        <h2 class="text-3xl font-bold text-slate-800 mb-2">Good morning, {{ explode(' ', auth()->user()->name)[0] }}.</h2>
        <p class="text-slate-600">Continue practicing how to inspect evidence before trusting familiar university messages.</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('practice.select') }}" class="bg-brand-teal text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-teal-700 transition text-sm">Start Practice</a>
        <a href="{{ route('check.index') }}" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-lg font-semibold hover:bg-slate-50 transition text-sm">Check Content</a>
    </div>
</div>

@if($stats['refresher_due'])
<div class="bg-brand-dark rounded-2xl p-6 text-white mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div>
        <h3 class="text-lg font-bold mb-1">Time for a refresher!</h3>
        <p class="text-sm text-slate-300">5 scenarios · about 5 minutes</p>
    </div>
    <a href="{{ route('refresher.start') }}" class="bg-brand-teal text-white px-5 py-2.5 rounded-lg font-bold hover:bg-teal-700 transition">
        Start Refresher &rarr;
    </a>
</div>
@endif

@if($stats['needs_diagnostic'])
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div>
        <h3 class="text-lg font-bold text-amber-800 mb-1">Take the diagnostic</h3>
        <p class="text-sm text-amber-700">Complete 5 scenarios to personalize your practice.</p>
    </div>
    <a href="{{ route('diagnostic.start') }}" class="bg-amber-500 text-white px-5 py-2.5 rounded-lg font-bold hover:bg-amber-600 transition">
        Start Diagnostic &rarr;
    </a>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Learning Profile Summary -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Learning Profile summary</h3>
                <p class="text-sm text-slate-500">Progress by scenario</p>
            </div>
            <div class="text-right">
                <span class="text-2xl font-bold text-slate-800">{{ $stats['accuracy'] }}%</span>
                <p class="text-xs text-slate-500 mt-1">Overall accuracy</p>
            </div>
        </div>

        <div class="space-y-5">
            @foreach($stats['progress'] as $label => $data)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-slate-700">{{ $label }}</span>
                    <span class="text-slate-500">{{ $data['correct'] }} / {{ $data['total'] }} correct</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div class="bg-brand-teal h-2 rounded-full" style="width: {{ $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-8 pt-6 border-t border-slate-100">
            <a href="{{ route('profile') }}" class="text-sm font-semibold text-brand-teal hover:underline flex items-center gap-1">View full Learning Profile &rarr;</a>
        </div>
    </div>

    <!-- Right Column -->
    <div class="space-y-6">
        <div class="bg-brand-dark rounded-2xl p-6 text-white relative overflow-hidden">
            <svg class="w-8 h-8 text-brand-teal mb-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
            <p class="text-xs text-slate-400 font-semibold mb-1">Common misconception</p>
            <h4 class="text-lg font-bold mb-2">HTTPS means the site is safe</h4>
            <p class="text-sm text-slate-300 mb-4">Review through URL / Web practice</p>
            <a href="{{ route('practice.select') }}" class="text-brand-teal text-sm font-semibold hover:underline flex items-center gap-1">Practice this concept &rarr;</a>
        </div>

        <!-- Recent Result Card (TYPE + TIME FIXED) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex items-center gap-5">
            @if($stats['recent'])
                @if($stats['recent']->mastery_achieved)
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                @else
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </div>
                @endif
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Most recent result</p>
                    <p class="text-sm font-bold text-slate-800">
                        {{ $stats['recent']->scenario?->type ?? 'Unknown' }}
                        · {{ $stats['recent']->mastery_achieved ? 'Correct' : 'Incorrect' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $stats['recent']->created_at->setTimezone(config('app.timezone'))->format('M d, Y g:i A') }}
                    </p>
                </div>
            @else
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Most recent result</p>
                    <p class="text-sm font-bold text-slate-800">No attempts yet</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bottom Row Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="w-8 h-8 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        <p class="text-xs text-slate-500 mb-1">Current focus</p>
        <p class="font-bold text-slate-800 mb-1">Verify the domain</p>
        <p class="text-sm text-slate-500">URL / Web Phishing</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="w-8 h-8 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-xs text-slate-500 mb-1">Practice activity</p>
        <p class="font-bold text-slate-800 mb-1">{{ $stats['total_complete'] }} recent attempts</p>
        <p class="text-sm text-slate-500">Across {{ count(array_filter($stats['progress'], fn($p) => $p['total'] > 0)) }} categories</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="w-8 h-8 bg-teal-50 text-brand-teal rounded-lg flex items-center justify-center mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </div>
        <p class="text-xs text-slate-500 mb-1">Content checks</p>
        <p class="font-bold text-slate-800 mb-1">{{ $stats['total_complete'] }} analyzed</p>
        <p class="text-sm text-slate-500">Correct: {{ $stats['accuracy'] }}%</p>
    </div>
</div>
@endsection