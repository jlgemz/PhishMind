@extends('layouts.app')
@section('header', 'Practice Scenario')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('practice.select') }}" class="text-sm text-slate-500 hover:text-brand-teal flex items-center gap-1">&larr; All scenarios</a>
        <span class="bg-white border border-slate-200 text-slate-500 text-xs px-3 py-1 rounded">Item</span>
    </div>

    <div class="mb-6">
        <p class="text-sm text-slate-500 mb-1">{{ $scenario->type }}</p>
        <h2 class="text-3xl font-bold text-slate-800">What would you do?</h2>
    </div>

    <div class="mb-8">
        @if($scenario->type === 'URL / Web Phishing')
            @php $domain = parse_url($scenario->content, PHP_URL_HOST) ?? $scenario->content; @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-[#f0f4f8] border-b border-slate-200 p-2.5 sm:p-3 flex items-center gap-2">
                    <div class="flex gap-1.5 shrink-0">
                        <div class="w-2.5 h-2.5 rounded-full bg-slate-300"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-slate-300"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-slate-300"></div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-lg text-xs text-slate-700 px-3 py-1.5 flex-1 min-w-0 ml-1.5 sm:ml-3 font-mono flex items-center gap-2 shadow-xs">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <div class="overflow-x-auto whitespace-nowrap scrollbar-thin py-0.5 select-all overscroll-x-contain w-full min-w-0">
                            <span class="inline-block font-mono text-slate-700">{{ $scenario->content }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-6 sm:p-12 md:p-16 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-brand-dark text-white rounded-xl flex items-center justify-center mb-6 shadow-lg"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2 truncate max-w-full px-4">{{ $domain }}</h3>
                    <p class="text-slate-500 mb-6 max-w-md text-sm px-4">This page is requesting you to sign in or view secure content.</p>
                    <button type="button" class="bg-brand-dark text-white px-6 py-2.5 rounded-lg font-medium hover:bg-slate-800 transition text-sm">Continue to {{ Str::limit($domain, 25) }}</button>
                </div>
            </div>
        @else
            @php
                $content = $scenario->content;
                $isSms = $scenario->type === 'SMS Phishing';
                $isEmail = $scenario->type === 'Email Phishing';
                $previewLabel = $isEmail ? 'Email preview' : 'Message preview';
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-[#f0f4f8] border-b border-slate-200 px-6 py-3 flex items-center gap-2 text-sm text-slate-600 font-semibold">
                    @if($isSms)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    @elseif($isEmail)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    @endif
                    {{ $previewLabel }}
                </div>
                <div class="p-4 sm:p-8 max-w-2xl mx-auto">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 sm:p-6 text-sm text-slate-700 whitespace-pre-line break-words overflow-x-auto">
                        {{ $content }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-8">
        <h3 class="text-xl font-bold text-slate-800 mb-6">Your assessment</h3>
        <form id="assessmentForm">
            <input type="hidden" name="scenario_id" value="{{ $scenario->id }}">
            <div class="mb-8">
                <label class="block text-sm font-bold text-slate-800 mb-3">How would you classify this item?</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="cursor-pointer"><input type="radio" name="student_decision" value="1" class="peer sr-only" required><div class="border border-slate-200 rounded-lg py-3 min-h-[44px] flex items-center justify-center text-center text-slate-600 peer-checked:border-brand-teal peer-checked:bg-teal-50 peer-checked:text-brand-teal font-semibold transition">Phishing</div></label>
                    <label class="cursor-pointer"><input type="radio" name="student_decision" value="0" class="peer sr-only"><div class="border border-slate-200 rounded-lg py-3 min-h-[44px] flex items-center justify-center text-center text-slate-600 peer-checked:border-brand-teal peer-checked:bg-teal-50 peer-checked:text-brand-teal font-semibold transition">Legitimate</div></label>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-800 mb-1">Why did you choose this?</label>
                <textarea name="student_reasoning" rows="5" class="w-full border border-slate-200 rounded-lg p-4 text-sm focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition" placeholder="Describe the evidence..." required></textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-brand-teal hover:bg-teal-700 text-white px-6 py-3 min-h-[44px] rounded-lg font-semibold flex items-center justify-center gap-2 transition w-full sm:w-auto">Submit assessment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('assessmentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.innerHTML = 'Evaluating...'; btn.disabled = true;
    const dataObject = Object.fromEntries(new FormData(e.target));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const res = await fetch('{{ route('learning.submit') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(dataObject)
        });
        if (res.redirected || res.status === 302 || res.status === 419) { alert("Session expired."); window.location.reload(); return; }
        if (res.status === 422) { const data = await res.json(); alert("Validation error. Check inputs."); btn.innerHTML = 'Submit'; btn.disabled = false; return; }
        const data = await res.json();
        if (data.success) { window.location.href = data.redirect; } else { alert("Error: " + data.message); btn.innerHTML = 'Submit'; btn.disabled = false; }
    } catch (err) { alert("Network error."); btn.innerHTML = 'Submit'; btn.disabled = false; }
});
</script>
@endsection