@extends('layouts.app')
@section('header', 'Diagnostic')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-brand-dark rounded-2xl p-5 sm:p-8 text-white mb-6 sm:mb-8 shadow-xs">
        <p class="text-xs font-bold text-teal-300 uppercase tracking-wider mb-2">Welcome to PhishMind</p>
        <h2 class="text-xl sm:text-2xl font-bold mb-2">Personalize your learning</h2>
        <p class="text-xs sm:text-sm text-slate-300">Answer 5 quick scenarios so we can tailor your practice to your weaknesses.</p>
    </div>

    <div id="diagnostic-container" class="space-y-6">
        @foreach($questions as $index => $scenario)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-8 shadow-xs {{ $index > 0 ? 'hidden' : '' }}" data-index="{{ $index }}">
            <div class="flex justify-between items-center mb-4">
                <p class="text-xs sm:text-sm font-bold text-slate-500">QUESTION {{ ($answeredCount ?? 0) + $index + 1 }} OF 5</p>
                <div class="w-24 sm:w-32 bg-slate-100 rounded-full h-2">
                    <div class="bg-brand-teal h-2 rounded-full" style="width: {{ round(((($answeredCount ?? 0) + $index + 1) / 5) * 100) }}%"></div>
                </div>
            </div>

            <div class="mb-3 flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                    {{ $scenario->type === 'URL / Web Phishing' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                       ($scenario->type === 'Email Phishing' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                       'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                    @if($scenario->type === 'URL / Web Phishing')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        URL Modality
                    @elseif($scenario->type === 'Email Phishing')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Email Modality
                    @else
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        SMS Modality
                    @endif
                </span>
                <span class="text-xs text-slate-400 font-medium">Evaluate the content below</span>
            </div>

            @php
                $isUrl = $scenario->type === 'URL / Web Phishing' || str_starts_with(trim($scenario->content), 'http://') || str_starts_with(trim($scenario->content), 'https://');
            @endphp
            @if($isUrl)
                <div class="mb-6 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
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
                </div>
            @elseif($scenario->type === 'Email Phishing')
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs mb-6">
                    <div class="bg-[#f0f4f8] border-b border-slate-200 px-4 py-2.5 flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Email Preview
                    </div>
                    <div class="p-4 sm:p-6 text-xs sm:text-sm text-slate-700 font-mono leading-relaxed whitespace-pre-line break-words overflow-x-auto">
                        {{ $scenario->content }}
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs mb-6">
                    <div class="bg-[#f0f4f8] border-b border-slate-200 px-4 py-2.5 flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        SMS Preview
                    </div>
                    <div class="p-4 sm:p-6 text-xs sm:text-sm text-slate-700 whitespace-pre-line break-words overflow-x-auto">
                        {{ $scenario->content }}
                    </div>
                </div>
            @endif

            <form class="diagnostic-form" data-scenario-id="{{ $scenario->id }}" data-index="{{ $index }}">
                @csrf
                <input type="hidden" name="scenario_id" value="{{ $scenario->id }}">
                <input type="hidden" name="student_decision" class="student-decision" value="">

                <div class="flex gap-3 sm:gap-4 mb-6">
                    <button type="button" class="decision-btn flex-1 border border-slate-200 rounded-xl py-3 font-bold text-slate-600 hover:border-brand-teal transition min-h-[44px]" data-value="1">Phishing</button>
                    <button type="button" class="decision-btn flex-1 border border-slate-200 rounded-xl py-3 font-bold text-slate-600 hover:border-brand-teal transition min-h-[44px]" data-value="0">Legitimate</button>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="w-full sm:w-auto bg-brand-teal text-white px-6 py-2.5 rounded-xl font-bold hover:bg-teal-700 transition disabled:bg-slate-300 disabled:cursor-not-allowed min-h-[44px] flex items-center justify-center" disabled>Submit &rarr;</button>
                </div>
            </form>
        </div>
        @endforeach
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

// Warn the user before they accidentally leave mid-diagnostic.
let diagnosticInProgress = true;
window.addEventListener('beforeunload', function (e) {
    if (diagnosticInProgress) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Handle decision buttons
document.querySelectorAll('.decision-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const form = this.closest('form');
        form.querySelectorAll('.decision-btn').forEach(b => {
            b.classList.remove('border-brand-teal', 'text-brand-teal', 'bg-teal-50');
        });
        this.classList.add('border-brand-teal', 'text-brand-teal', 'bg-teal-50');

        form.querySelector('.student-decision').value = this.dataset.value;
        form.querySelector('button[type="submit"]').disabled = false;
    });
});

// Handle form submit
document.querySelectorAll('.diagnostic-form').forEach((form, index) => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Saving...';
        btn.disabled = true;

        const formData = new FormData(form);
        const dataObject = Object.fromEntries(formData);

        try {
            const res = await fetch('{{ route('diagnostic.submit') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(dataObject)
            });

            // Handle 422 validation
            if (res.status === 422) {
                const data = await res.json();
                let msg = 'Validation error:\n';
                for (const [key, messages] of Object.entries(data.errors)) {
                    msg += `- ${messages.join(', ')}\n`;
                }
                alert(msg);
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            // Handle 419 / session expiry
            if (res.status === 419) {
                alert('Session expired. Reloading...');
                window.location.reload();
                return;
            }

            const data = await res.json();

            if (data.success) {
                const currentDiv = form.closest('.bg-white');
                const nextDiv = document.querySelector(`[data-index="${index + 1}"]`);

                if (nextDiv) {
                    currentDiv.classList.add('hidden');
                    nextDiv.classList.remove('hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    // All done → no navigation warning, then go to dashboard
                    diagnosticInProgress = false;
                    window.location.href = '{{ route('dashboard') }}';
                }
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Network error: ' + err.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
});
</script>
@endsection