@extends('layouts.app')

@section('header', 'Check Suspicious Content')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <p class="text-xs text-brand-teal font-semibold uppercase tracking-wider mb-2">DECISION SUPPORT</p>
        <h2 class="text-3xl font-bold text-slate-800 mb-2">Check suspicious content</h2>
        <p class="text-slate-600">Paste a URL, email, or message to see a Risk Assessment.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 sm:p-8 shadow-xs">
            <form action="{{ route('check.analyze') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-bold text-slate-800 mb-3">Content types:</label>
                    <div class="flex flex-wrap gap-2.5 sm:gap-3">
                        <label class="cursor-pointer flex-1 sm:flex-none">
                            <input type="radio" name="type" value="url" class="peer sr-only" checked>
                            <div class="flex items-center justify-center gap-2 border border-slate-200 rounded-xl px-4 sm:px-5 py-2.5 text-slate-600 peer-checked:border-brand-teal peer-checked:bg-teal-50 peer-checked:text-brand-teal font-semibold transition min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg> URL
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1 sm:flex-none">
                            <input type="radio" name="type" value="email" class="peer sr-only">
                            <div class="flex items-center justify-center gap-2 border border-slate-200 rounded-xl px-4 sm:px-5 py-2.5 text-slate-600 peer-checked:border-brand-teal peer-checked:bg-teal-50 peer-checked:text-brand-teal font-semibold transition min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> Email
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1 sm:flex-none">
                            <input type="radio" name="type" value="sms" class="peer sr-only">
                            <div class="flex items-center justify-center gap-2 border border-slate-200 rounded-xl px-4 sm:px-5 py-2.5 text-slate-600 peer-checked:border-brand-teal peer-checked:bg-teal-50 peer-checked:text-brand-teal font-semibold transition min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> Messages
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-6 sm:mb-8">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Paste the content</label>
                    <textarea name="content" rows="6" class="w-full border border-slate-200 rounded-xl p-4 text-sm font-mono focus:ring-2 focus:ring-brand-teal focus:border-brand-teal outline-none transition" placeholder="Paste a URL, email body, or message text..." required></textarea>
                </div>

                <button type="submit" class="w-full sm:w-auto justify-center bg-brand-teal hover:bg-teal-700 text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 transition min-h-[44px]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> Analyze content
                </button>
            </form>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="bg-[#fef3c7] rounded-2xl border border-amber-200 p-6">
                <svg class="w-6 h-6 text-amber-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h4 class="font-bold text-slate-800 mb-2">Before you paste</h4>
                <p class="text-sm text-slate-700 leading-relaxed">Remove passwords, one-time codes, financial details, and other private information. This prototype uses a local model and cloud AI fallback.</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    What the result shows
                </h4>
                <ul class="text-sm text-slate-600 space-y-3">
                    <li>Random Forest verdict</li>
                    <li>SHAP Risk Indicators</li>
                    <li>Grounded explanation</li>
                    <li>Cybersecurity recommendation</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection