@extends('layouts.app')

@section('header', 'Targeted Practice')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-2">MISCONCEPTION-AWARE ADAPTIVE FEEDBACK</p>
        <h2 class="text-3xl font-bold text-slate-800 mb-2">Test the idea again in a new context</h2>
        <p class="text-slate-600">This retry focuses on the possible misconception found in your reasoning, not only whether the previous answer was correct.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Misconception Correction -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 sm:p-8 shadow-sm">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 bg-[#fef3c7] text-[#d97706] rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-1">POSSIBLE MISCONCEPTION</p>
                    <h3 class="font-bold text-slate-800 text-xl">
                        {{ isset($record->misconceptions[0]['name']) ? $record->misconceptions[0]['name'] : 'HTTPS means the site is safe' }}
                    </h3>
                </div>
            </div>

            <div class="border-l-4 border-brand-teal pl-5 mb-8">
                <p class="text-sm font-bold text-slate-800 mb-2">Correction to carry forward</p>
                <p class="text-slate-600 text-sm leading-relaxed">
                    {{ isset($record->misconceptions[0]['description']) ? $record->misconceptions[0]['description'] : 'HTTPS protects data while it travels to a site. It does not prove that the site belongs to the university or that its request is trustworthy.' }}
                </p>
            </div>

            <div class="bg-[#f0f4f8] rounded-xl p-6">
                <p class="text-sm font-bold text-slate-700 mb-4">In the next example, ask:</p>
                <ul class="space-y-3 text-sm text-slate-600">
                    <li class="flex items-center gap-3"><svg class="w-4 h-4 text-brand-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> What independently verifies who owns this service?</li>
                    <li class="flex items-center gap-3"><svg class="w-4 h-4 text-brand-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Is the task expected and consistent with the official workflow?</li>
                    <li class="flex items-center gap-3"><svg class="w-4 h-4 text-brand-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Am I treating one familiar clue as proof of legitimacy?</li>
                </ul>
            </div>
        </div>

        <!-- Right: Retry Action -->
        <div class="bg-brand-dark rounded-2xl p-5 sm:p-8 text-white flex flex-col justify-between">
            <div>
                <p class="text-slate-400 text-sm mb-6">Retry loop</p>
                <div class="flex items-center gap-4 mb-8 relative">
                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-slate-700 -z-0"></div>
                    <div class="w-8 h-8 rounded-full bg-brand-teal flex items-center justify-center font-bold text-sm z-10 relative">✓</div>
                    <div class="w-8 h-8 rounded-full border-2 border-brand-teal text-brand-teal bg-brand-dark flex items-center justify-center font-bold text-sm z-10 relative">2</div>
                    <div class="w-8 h-8 rounded-full border border-slate-600 text-slate-500 bg-brand-dark flex items-center justify-center font-bold text-sm z-10 relative">3</div>
                </div>
                <h4 class="text-xl font-bold mb-3">Related example ready</h4>
                <p class="text-sm text-slate-300 mb-6 leading-relaxed">Next: compare a legitimate university library address with the suspicious identity cues you just reviewed.</p>
                <p class="text-xs text-slate-400 flex items-center gap-2 mb-8">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Your first response is saved to the Learning Profile.
                </p>
            </div>
            <a href="{{ route('practice.select') }}" class="bg-brand-teal hover:bg-teal-700 text-white text-center w-full py-3 rounded-lg font-semibold transition flex items-center justify-center gap-2">
                Try Again &rarr;
            </a>
        </div>
    </div>
</div>
@endsection