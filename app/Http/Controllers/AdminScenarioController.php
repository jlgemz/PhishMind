<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use Illuminate\Http\Request;

class AdminScenarioController extends Controller
{
    public function index()
    {
        $scenarios = Scenario::latest()->paginate(15);
        return view('admin.scenarios.index', compact('scenarios'));
    }

    public function create()
    {
        return view('admin.scenarios.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'          => 'required|string',
            'content'       => 'required|string',
            'is_phishing'   => 'required|boolean',
            'indicator'     => 'required|string|in:urgency,deceptive_url,impersonation',
            'status'        => 'required|in:draft,approved',
            'is_diagnostic' => 'nullable|boolean',
            'explanation'   => 'nullable|string',
        ]);

        Scenario::create($validated);
        return redirect()->route('admin.scenarios.index')->with('success', 'Scenario created successfully.');
    }

    public function edit(Scenario $scenario)
    {
        return view('admin.scenarios.form', compact('scenario'));
    }

    public function update(Request $request, Scenario $scenario)
    {
        $validated = $request->validate([
            'type'          => 'required|string',
            'content'       => 'required|string',
            'is_phishing'   => 'required|boolean',
            'indicator'     => 'required|string|in:urgency,deceptive_url,impersonation',
            'status'        => 'required|in:draft,approved',
            'is_diagnostic' => 'nullable|boolean',
            'explanation'   => 'nullable|string',
        ]);

        $scenario->update($validated);
        return redirect()->route('admin.scenarios.index')->with('success', 'Scenario updated successfully.');
    }

    public function destroy(Scenario $scenario)
    {
        $scenario->delete();
        return redirect()->route('admin.scenarios.index')->with('success', 'Scenario deleted.');
    }

    public function approve(Scenario $scenario)
    {
        $scenario->update(['status' => 'approved']);
        return back()->with('success', "Scenario #{$scenario->id} approved.");
    }

    public function reject(Scenario $scenario)
    {
        $scenario->update(['status' => 'draft']);
        return back()->with('success', "Scenario #{$scenario->id} moved to draft.");
    }
}