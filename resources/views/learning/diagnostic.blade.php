@extends('layouts.app')
@section('header', 'Diagnostic')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-brand-dark rounded-2xl p-8 text-white mb-8">
        <p class="text-xs font-bold text-brand-teal uppercase tracking-wider mb-2">Welcome to PhishMind</p>
        <h2 class="text-2xl font-bold mb-2">Personalize your learning</h2>
        <p class="text-slate-300">Answer 5 quick scenarios so we can tailor your practice to your weaknesses.</p>
    </div>

    <div id="diagnostic-container" class="space-y-6">
        @foreach($questions as $index => $scenario)
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm {{ $index > 0 ? 'hidden' : '' }}" data-index="{{ $index }}">
            <div class="flex justify-between items-center mb-4">
                <p class="text-sm font-bold text-slate-500">QUESTION {{ $index + 1 }} OF {{ $questions->count() }}</p>
                <div class="w-32 bg-slate-100 rounded-full h-2">
                    <div class="bg-brand-teal h-2 rounded-full" style="width: {{ round((($index) / $questions->count()) * 100) }}%"></div>
                </div>
            </div>

            <div class="bg-[#f0f4f8] p-4 rounded-xl text-sm text-slate-700 font-mono leading-relaxed break-words mb-6">
                {{ $scenario->content }}
            </div>

            <form class="diagnostic-form" data-scenario-id="{{ $scenario->id }}" data-index="{{ $index }}">
                @csrf
                <input type="hidden" name="scenario_id" value="{{ $scenario->id }}">
                <input type="hidden" name="student_decision" class="student-decision" value="">

                <div class="flex gap-4 mb-6">
                    <button type="button" class="decision-btn flex-1 border border-slate-200 rounded-lg py-3 font-bold text-slate-600 hover:border-brand-teal transition" data-value="1">Phishing</button>
                    <button type="button" class="decision-btn flex-1 border border-slate-200 rounded-lg py-3 font-bold text-slate-600 hover:border-brand-teal transition" data-value="0">Legitimate</button>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-brand-teal text-white px-6 py-2 rounded-lg font-bold hover:bg-teal-700 transition disabled:bg-slate-300 disabled:cursor-not-allowed" disabled>Submit &rarr;</button>
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