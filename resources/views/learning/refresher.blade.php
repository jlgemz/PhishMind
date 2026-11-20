@extends('layouts.app')
@section('header', 'Refresher Practice')
@section('content')

@if($refresherDue)
<div class="bg-brand-dark rounded-2xl p-6 text-white mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-brand-teal rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </div>
        <div>
            <p class="text-xs text-brand-teal font-bold uppercase tracking-wider mb-1">Time for a refresher</p>
            <h3 class="text-lg font-bold mb-1">Your personalized review is ready</h3>
            <p class="text-sm text-slate-300">5 scenarios · about 5 minutes · weighted toward your weakest indicator</p>
        </div>
    </div>
    <a href="{{ route('refresher.start') }}" class="bg-brand-teal hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-bold text-sm transition flex items-center gap-2 shrink-0">
        Start Refresher &rarr;
    </a>
</div>
@endif

<div class="max-w-4xl mx-auto">
    <div class="bg-brand-dark rounded-2xl p-8 text-white mb-8">
        <h2 class="text-2xl font-bold mb-2">Personalized Refresher</h2>
        <p class="text-slate-300">5 scenarios · about 5 minutes</p>
    </div>

    <div id="refresher-container" class="space-y-6">
        @foreach($questions as $index => $scenario)
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm {{ $index > 0 ? 'hidden' : '' }}" data-index="{{ $index }}">
            <p class="text-sm font-bold text-slate-500 mb-2">QUESTION {{ $index + 1 }} OF {{ $questions->count() }}</p>
            <div class="bg-[#f0f4f8] p-4 rounded-xl text-sm text-slate-700 font-mono leading-relaxed break-words mb-6">
                {{ $scenario->content }}
            </div>
            
            <form class="refresher-form" data-scenario-id="{{ $scenario->id }}" data-index="{{ $index }}">
                @csrf
                <input type="hidden" name="scenario_id" value="{{ $scenario->id }}">
                <input type="hidden" name="session_type" value="refresher">
                <input type="hidden" name="student_decision" class="student-decision" value="">
                
                <div class="flex gap-4 mb-6">
                    <button type="button" class="decision-btn flex-1 border rounded-lg py-3 font-bold text-slate-600 hover:border-brand-teal" data-value="1">Phishing</button>
                    <button type="button" class="decision-btn flex-1 border rounded-lg py-3 font-bold text-slate-600 hover:border-brand-teal" data-value="0">Legitimate</button>
                </div>

                <input type="hidden" name="student_reasoning" value="Refresher practice">

                <div class="flex justify-end">
                    <button type="submit" class="bg-brand-teal text-white px-6 py-2 rounded-lg font-bold hover:bg-teal-700" disabled>Submit</button>
                </div>
            </form>
        </div>
        @endforeach
    </div>
</div>

<script>
document.querySelectorAll('.refresher-form').forEach((form, index) => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        
        try {
            const res = await fetch('{{ route('learning.submit') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(Object.fromEntries(formData))
            });
            const data = await res.json();
            
            if (data.success) {
                // Redirect to feedback page for this scenario
                window.location.href = '{{ route('refresher.feedback') }}?scenario_id=' 
                    + form.dataset.scenarioId 
                    + '&decision=' 
                    + form.querySelector('.student-decision').value;
            }
        } catch (err) {
            alert('Error saving result.');
        }
    });
});
</script>
@endsection