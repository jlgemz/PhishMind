@extends('layouts.admin')
@section('header', 'Manage Scenarios')
@section('content')

@if(session('success'))
<div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-4 text-sm font-bold border border-emerald-200">
    ✅ {{ session('success') }}
</div>
@endif

<div class="mb-6 flex justify-between items-center">
    <p class="text-slate-600">Create, edit, approve, or delete practice scenarios.</p>
    <a href="{{ route('admin.scenarios.create') }}" class="bg-brand-teal text-white px-4 py-2 rounded-lg font-bold hover:bg-teal-700 transition">
        + Add Scenario
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-x-auto">
    <table class="w-full text-left text-sm min-w-[900px]">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
            <tr>
                <th class="px-6 py-3 font-semibold">ID</th>
                <th class="px-6 py-3 font-semibold">Type</th>
                <th class="px-6 py-3 font-semibold">Indicator</th>
                <th class="px-6 py-3 font-semibold">Content</th>
                <th class="px-6 py-3 font-semibold">Label</th>
                <th class="px-6 py-3 font-semibold">Status</th>
                <th class="px-6 py-3 font-semibold text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($scenarios as $scenario)
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $scenario->id }}</td>
                <td class="px-6 py-3 font-bold text-slate-800 text-xs">{{ $scenario->type }}</td>
                <td class="px-6 py-3 text-xs">
                    <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 font-mono">
                        {{ $scenario->indicator ?? '—' }}
                    </span>
                </td>
                <td class="px-6 py-3 text-slate-600 truncate max-w-xs">{{ Str::limit($scenario->content, 60) }}</td>
                <td class="px-6 py-3">
                    <span class="px-2 py-1 rounded text-xs font-bold
                        {{ $scenario->is_phishing ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $scenario->is_phishing ? 'Phishing' : 'Legitimate' }}
                    </span>
                </td>
                <td class="px-6 py-3">
                    <span class="px-2 py-1 rounded text-xs font-bold
                        {{ $scenario->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ ucfirst($scenario->status) }}
                    </span>
                </td>
                <td class="px-6 py-3 text-right">
                    <div class="flex justify-end gap-2 flex-wrap">
                        @if($scenario->status !== 'approved')
                        <form action="{{ route('admin.scenarios.approve', $scenario->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-emerald-600 font-bold hover:underline text-xs">Approve</button>
                        </form>
                        @endif
                        @if($scenario->status === 'approved')
                        <form action="{{ route('admin.scenarios.reject', $scenario->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-amber-600 font-bold hover:underline text-xs">Reject</button>
                        </form>
                        @endif
                        <a href="{{ route('admin.scenarios.edit', $scenario->id) }}" class="text-brand-teal font-bold hover:underline text-xs">Edit</a>
                        <form action="{{ route('admin.scenarios.destroy', $scenario->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-rose-500 font-bold hover:underline text-xs">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                    No scenarios yet. Click "Add Scenario" to create one.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $scenarios->links() }}</div>
@endsection