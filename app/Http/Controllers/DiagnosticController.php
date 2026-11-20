<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\LearningRecord;
use App\Services\RefresherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiagnosticController extends Controller
{
    protected $refresher;

    public function __construct(RefresherService $refresher)
    {
        $this->refresher = $refresher;
    }

    /**
     * Show the diagnostic (5 scenarios for new users).
     * Resumes an in-progress diagnostic if the session still holds the IDs.
     */
    public function start()
    {
        $userId = auth()->id();

        // Already completed → never show again.
        if (auth()->user()->diagnostic_completed_at) {
            return redirect()->route('dashboard')
                ->with('info', 'You have already completed the diagnostic.');
        }

        // Try to resume an in-progress diagnostic from the session.
        $ids       = session('diagnostic_scenario_ids');
        $questions = collect();

        if ($ids && count($ids) === 5) {
            $questions = Scenario::whereIn('id', $ids)->get()
                ->sortBy(fn ($q) => array_search($q->id, $ids))
                ->values();
        }

        // Otherwise, pick a fresh set of 5.
        if ($questions->isEmpty()) {
            $questions = Scenario::where('status', 'approved')
                ->where('is_diagnostic', true)
                ->inRandomOrder()
                ->take(5)
                ->get();

            if ($questions->isEmpty()) {
                $questions = Scenario::where('status', 'approved')
                    ->inRandomOrder()
                    ->take(5)
                    ->get();
            }

            if ($questions->isEmpty()) {
                return redirect()->route('dashboard')
                    ->with('error', 'No scenarios available for the diagnostic.');
            }

            session(['diagnostic_scenario_ids' => $questions->pluck('id')->toArray()]);
        }

        // Skip questions the user already answered in this diagnostic run.
        $answeredIds = LearningRecord::where('user_id', $userId)
            ->where('session_type', 'diagnostic')
            ->whereIn('scenario_id', $questions->pluck('id'))
            ->pluck('scenario_id')
            ->toArray();

        $questions = $questions->reject(fn ($q) => in_array($q->id, $answeredIds))->values();

        // Safety net: if everything in the session is already answered
        // (e.g. last submit response was lost), mark complete now.
        if ($questions->isEmpty()) {
            auth()->user()->update(['diagnostic_completed_at' => now()]);
            session()->forget('diagnostic_scenario_ids');

            return redirect()->route('dashboard')
                ->with('success', 'Diagnostic completed!');
        }

        session(['session_type' => 'diagnostic']);

        return view('learning.diagnostic', compact('questions'));
    }

    /**
     * Submit each diagnostic answer via AJAX.
     */
    public function submit(Request $request)
    {
        @set_time_limit(120);

        $validated = $request->validate([
            'scenario_id'       => 'required|exists:scenarios,id',
            'student_decision'  => 'required|in:0,1',
            'student_reasoning' => 'nullable|string|max:1000',
        ]);

        $scenario = Scenario::findOrFail($validated['scenario_id']);

        // Call ML service
        try {
            $mlUrl = rtrim(config('services.ml_service.url', 'http://127.0.0.1:8001'), '/');
            $response = Http::timeout(35)->post("{$mlUrl}/adaptive/evaluate", [
                'url'               => $scenario->content,
                'scenario_type'     => $scenario->type,
                'student_decision'  => (int) $validated['student_decision'],
                'student_reasoning' => $validated['student_reasoning'] ?? 'Diagnostic response',
                'is_phishing'       => (bool) $scenario->is_phishing,
            ]);

            if ($response->successful()) {
                $mlResult = $response->json();
            } else {
                Log::warning('Python error: ' . $response->body());
                $mlResult = $this->getMockMLResponse($scenario, $validated['student_decision']);
            }
        } catch (\Exception $e) {
            Log::warning('Python offline: ' . $e->getMessage());
            $mlResult = $this->getMockMLResponse($scenario, $validated['student_decision']);
        }

        $modelPrediction = (bool) ($mlResult['prediction'] ?? $scenario->is_phishing);
        $confidence      = (float) ($mlResult['confidence'] ?? 0.86);
        $isMastered      = ((bool) $scenario->is_phishing === (bool) $validated['student_decision']);

        // Save the record with session_type = 'diagnostic'
        LearningRecord::create([
            'user_id'              => auth()->id(),
            'scenario_id'          => $scenario->id,
            'scenario_url'         => $scenario->content,
            'model_prediction'     => $modelPrediction,
            'student_decision'     => (bool) $validated['student_decision'],
            'model_confidence'     => round($confidence * 100),
            'shap_risk_indicators' => $mlResult['shap_risk_indicators'] ?? [],
            'misconceptions'       => $mlResult['misconceptions'] ?? [],
            'targeted_practice'    => $mlResult['targeted_practice'] ?? [],
            'llm_explanation'      => $mlResult['explanation'] ?? 'No explanation available.',
            'mastery_achieved'     => $isMastered,
            'session_type'         => 'diagnostic',
            'attempts'             => 1,
        ]);

        // Update the per-indicator learning profile
        if ($scenario->indicator) {
            $this->refresher->updateProfile(
                auth()->id(),
                $scenario->indicator,
                $isMastered
            );
        }

        // Detect whether the full diagnostic is now complete.
        $diagnosticIds      = session('diagnostic_scenario_ids', []);
        $diagnosticComplete = false;

        if (!empty($diagnosticIds)) {
            $answered = LearningRecord::where('user_id', auth()->id())
                ->where('session_type', 'diagnostic')
                ->whereIn('scenario_id', $diagnosticIds)
                ->distinct('scenario_id')
                ->count('scenario_id');

            if ($answered >= count($diagnosticIds)) {
                auth()->user()->update(['diagnostic_completed_at' => now()]);
                session()->forget('diagnostic_scenario_ids');
                $diagnosticComplete = true;
            }
        }

        return response()->json([
            'success'             => true,
            'correct'             => $isMastered,
            'message'             => $isMastered ? 'Correct!' : 'Not quite.',
            'diagnostic_complete' => $diagnosticComplete,
        ]);
    }

    private function getMockMLResponse($scenario, $decision)
    {
        $isCorrect = ((bool) $scenario->is_phishing === (bool) $decision);

        return [
            'prediction'           => $scenario->is_phishing,
            'confidence'           => 0.86,
            'mastery_achieved'     => $isCorrect,
            'shap_risk_indicators' => [
                ['feature' => 'Domain differs from official university domain', 'value' => '+0.34'],
                ['feature' => 'Credential request', 'value' => '+0.25'],
            ],
            'misconceptions'       => [],
            'targeted_practice'    => [],
            'explanation'          => 'Fallback explanation (Python offline).',
        ];
    }
}