@extends('layouts.admin')
@section('header', 'Student Learning Results')
@section('content')

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[640px]">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
            <tr>
                <th class="px-6 py-3 font-semibold">Student</th>
                <th class="px-6 py-3 font-semibold">Scenario</th>
                <th class="px-6 py-3 font-semibold">Model Verdict</th>
                <th class="px-6 py-3 font-semibold">Student Choice</th>
                <th class="px-6 py-3 font-semibold">Mastery</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($results as $result)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <p class="font-bold">{{ $result->user->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-slate-500">{{ $result->user->email ?? '' }}</p>
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        @php
                            $raw = (string) $result->scenario_url;
                            $isUrl = str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://');
                        @endphp
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 mr-2">
                            {{ $isUrl ? 'URL' : 'Text' }}
                        </span>
                        <span class="truncate max-w-xs inline-block align-middle">
                            {{ Str::limit($raw, 40) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        {{ $result->model_prediction ? 'Phishing' : 'Legitimate' }}
                    </td>
                    <td class="px-6 py-4">
                        {{ $result->student_decision ? 'Phishing' : 'Legitimate' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($result->mastery_achieved)
                            <span class="text-emerald-600 font-bold">Passed</span>
                        @else
                            <span class="text-rose-500 font-bold">Failed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">
                        No attempts yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-4">{{ $results->links() }}</div>

@endsection