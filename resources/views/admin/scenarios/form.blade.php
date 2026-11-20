@extends('layouts.admin')
@section('header', isset($scenario) ? 'Edit Scenario' : 'Create Scenario')
@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 max-w-3xl">
    <form action="{{ isset($scenario) ? route('admin.scenarios.update', $scenario->id) : route('admin.scenarios.store') }}" method="POST">
        @csrf
        @if(isset($scenario)) @method('PUT') @endif

        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">Scenario Type</label>
            <select name="type" class="w-full border rounded-lg px-4 py-2" required>
                <option value="URL / Web Phishing"        {{ (isset($scenario) && $scenario->type == 'URL / Web Phishing') ? 'selected' : '' }}>URL / Web Phishing</option>
                <option value="Email Phishing"            {{ (isset($scenario) && $scenario->type == 'Email Phishing') ? 'selected' : '' }}>Email Phishing</option>
                <option value="SMS Phishing"              {{ (isset($scenario) && $scenario->type == 'SMS Phishing') ? 'selected' : '' }}>SMS Phishing</option>
                <option value="Messaging & Social Media"  {{ (isset($scenario) && $scenario->type == 'Messaging & Social Media') ? 'selected' : '' }}>Messaging & Social Media</option>
                <option value="Spear Phishing"            {{ (isset($scenario) && $scenario->type == 'Spear Phishing') ? 'selected' : '' }}>Spear Phishing</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">Ground Truth (Label)</label>
            <select name="is_phishing" class="w-full border rounded-lg px-4 py-2" required>
                <option value="1" {{ (isset($scenario) && $scenario->is_phishing) ? 'selected' : '' }}>Phishing (1)</option>
                <option value="0" {{ (isset($scenario) && !$scenario->is_phishing) ? 'selected' : '' }}>Legitimate (0)</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">Content (URL, Email Body, or SMS Text)</label>
            <textarea name="content" rows="6" class="w-full border rounded-lg px-4 py-2 font-mono text-sm" required>{{ $scenario->content ?? '' }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold mb-2">Explanation (for the result page)</label>
            <textarea name="explanation" rows="3" class="w-full border rounded-lg px-4 py-2 text-sm">{{ $scenario->explanation ?? '' }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.scenarios.index') }}" class="px-4 py-2 border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-brand-teal text-white font-bold rounded-lg hover:bg-teal-700">Save & Publish</button>
        </div>
    </form>
</div>
@endsection