@extends('layouts.app')

@section('header', 'Learning Profile')

@section('content')
<div x-data="{ showAll: false, filterOpen: false, currentFilter: 'all' }">
    
    <div class="mb-8">
        <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-1">LEARNING PROFILE</p>
        <h2 class="text-3xl font-bold text-slate-800 mb-2">Your awareness practice</h2>
        <p class="text-slate-600">Review your real progress, reasoning patterns, and recent attempts.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Progress by Scenario -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Progress by scenario</h3>
                    <p class="text-sm text-slate-500">{{ $stats['totalAttempts'] }} total practice items</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-slate-800">{{ $stats['accuracy'] }}%</p>
                    <p class="text-xs text-slate-500">accuracy</p>
                </div>
            </div>
            <div class="space-y-5">
                @foreach($stats['progress'] as $label => $data)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700">{{ $label }}</span>
                        <span class="text-slate-500">{{ $data['correct'] }} / {{ $data['total'] }} correct</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5">
                        <div class="bg-brand-teal h-2.5 rounded-full" style="width: {{ $data['total'] > 0 ? round(($data['correct'] / $data['total']) * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Misconceptions & Score -->
        <div class="space-y-4">
            @if($stats['mostCommonMisconception'])
            <div class="bg-[#fef3c7] rounded-2xl border border-amber-200 p-5">
                <p class="text-xs font-bold text-amber-700 uppercase mb-1">ACTIVE MISCONCEPTION</p>
                <p class="font-bold text-slate-800 mb-2 text-sm">{{ $stats['mostCommonMisconception'] }}</p>
                <p class="text-xs text-amber-800">Detected in your reasoning patterns.</p>
            </div>
            @else
            <div class="bg-[#dcfce7] rounded-2xl border border-emerald-200 p-5">
                <p class="text-xs font-bold text-emerald-700 uppercase mb-1">NO MISCONCEPTIONS</p>
                <p class="font-bold text-slate-800 mb-2 text-sm">You're reasoning clearly!</p>
                <p class="text-xs text-emerald-800">Keep practicing to maintain your accuracy.</p>
            </div>
            @endif

            <div class="bg-[#dcfce7] rounded-2xl border border-emerald-200 p-5">
                <p class="text-xs font-bold text-emerald-700 uppercase mb-1 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> 
                    SCORE
                </p>
                <p class="font-bold text-slate-800 mb-2 text-sm">{{ $stats['correctAttempts'] }} correct out of {{ $stats['totalAttempts'] }}</p>
                <p class="text-xs text-emerald-800">Overall accuracy: {{ $stats['accuracy'] }}%</p>
            </div>
        </div>
    </div>

    <!-- Progress by Indicator -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm mb-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Progress by Indicator</h3>
                <p class="text-sm text-slate-500">Your accuracy across each phishing awareness concept</p>
            </div>
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
        </div>

        @php $hasIndicatorData = collect($stats['indicatorStats'] ?? [])->sum('total') > 0; @endphp

        @if($hasIndicatorData)
            <div class="space-y-5">
                @foreach($stats['indicatorStats'] as $label => $data)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700">{{ $label }}</span>
                        <span class="text-slate-500">
                            {{ $data['correct'] }} / {{ $data['total'] }} 
                            @if($data['total'] > 0)
                                ({{ $data['rate'] }}%)
                            @endif
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2.5">
                        <div class="bg-brand-teal h-2.5 rounded-full transition-all duration-500" style="width: {{ $data['rate'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            @php
                $weakest = collect($stats['indicatorStats'])->filter(fn($i) => $i['total'] > 0)->sortBy('rate')->first();
            @endphp
            @if($weakest)
            <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <p class="text-sm font-bold text-amber-800">Focus area</p>
                    <p class="text-sm text-amber-700">Your weakest indicator will be prioritized in the next refresher.</p>
                </div>
            </div>
            @endif
        @else
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 text-center">
                <p class="text-sm text-slate-500">No practice attempts yet. Complete a few scenarios to see your progress by indicator.</p>
                <a href="{{ route('practice.select') }}" class="inline-block mt-3 bg-brand-teal text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-teal-700 transition">
                    Start Practicing &rarr;
                </a>
            </div>
        @endif
    </div>

    <!-- Attempt History (TYPE + TIME FIXED) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Attempt history</h3>
                <p class="text-sm text-slate-500">Recent classifications and results</p>
            </div>
            
            <div class="relative">
                <button @click="filterOpen = !filterOpen" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-500 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    <span class="text-xs font-semibold uppercase" x-text="currentFilter === 'all' ? 'Filter' : currentFilter"></span>
                </button>
                
                <div x-show="filterOpen" @click.outside="filterOpen = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                    <button @click="currentFilter = 'all'; filterOpen = false" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition">All Attempts</button>
                    <button @click="currentFilter = 'correct'; filterOpen = false" class="block w-full text-left px-4 py-2 text-sm text-emerald-600 hover:bg-slate-50 transition">Correct Only</button>
                    <button @click="currentFilter = 'review'; filterOpen = false" class="block w-full text-left px-4 py-2 text-sm text-amber-600 hover:bg-slate-50 transition">Needs Review</button>
                </div>
            </div>
        </div>
        
        <div class="divide-y divide-slate-100">
            @forelse($records as $index => $record)
                <!-- WRAPPED IN AN ANCHOR TAG TO MAKE IT CLICKABLE -->
                <a href="{{ route('learning.result', $record->id) }}" 
                   x-show="
                    (currentFilter === 'all' || 
                    (currentFilter === 'correct' && {{ $record->mastery_achieved ? 'true' : 'false' }}) || 
                    (currentFilter === 'review' && {{ $record->mastery_achieved ? 'false' : 'true' }})) 
                    && (showAll || {{ $index }} < 3)" 
                    class="p-6 flex justify-between items-center transition hover:bg-slate-50 block">
                    
                    <div>
                        <p class="font-bold text-slate-800 text-sm">
                            {{ $record->scenario?->type ?? 'Unknown' }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> 
                            {{ $record->created_at->setTimezone(config('app.timezone'))->format('M d, Y g:i A') }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1 truncate max-w-xs">{{ Str::limit($record->scenario_url, 50) }}</p>
                    </div>
                    <div class="flex items-center gap-8">
                        <div class="text-right">
                            <p class="text-xs text-slate-500 mb-0.5">Your choice</p>
                            <p class="font-semibold text-slate-800 text-sm">{{ $record->student_decision ? 'Phishing' : 'Legitimate' }}</p>
                        </div>
                        
                        @if($record->mastery_achieved)
                            <span class="px-3 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Correct</span>
                        @else
                            <span class="px-3 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">Review</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-slate-500 text-sm">
                    You haven't completed any practice attempts yet. 
                    <a href="{{ route('practice.select') }}" class="text-brand-teal font-bold hover:underline">Start practicing now!</a>
                </div>
            @endforelse
        </div>

        @if($records->count() > 3)
        <div x-show="currentFilter === 'all'" class="p-4 border-t border-slate-200 text-center">
            <button @click="showAll = !showAll" class="text-sm font-semibold text-brand-teal hover:underline flex items-center justify-center gap-1 w-full">
                <span x-text="showAll ? 'Show fewer attempts' : 'Show all attempts'"></span>
                <svg :class="{'rotate-180': showAll}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>
        @endif
    </div>
</div>
@endsection