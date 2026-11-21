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
    /**
     * Show the diagnostic (5 scenarios for new users covering URL, Email, and SMS).
     * Resumes an in-progress diagnostic if the session holds the 5 IDs.
     */
    public function start()
    {
        $userId = auth()->id();

        // Allow restarting the diagnostic via query param (e.g. /diagnostic?restart=1)
        if (request()->has('restart') || request()->has('retake')) {
            auth()->user()->update(['diagnostic_completed_at' => null]);
            LearningRecord::where('user_id', $userId)
                ->where('session_type', 'diagnostic')
                ->delete();
            session()->forget('diagnostic_scenario_ids');
        }

        // Already completed 5 questions → never show again (unless user restarted above).
        if (auth()->user()->diagnostic_completed_at) {
            $recordCount = LearningRecord::where('user_id', $userId)
                ->where('session_type', 'diagnostic')
                ->count();

            // If user previously completed a buggy 3-question diagnostic, allow fresh 5-question retake
            if ($recordCount < 5) {
                auth()->user()->update(['diagnostic_completed_at' => null]);
                LearningRecord::where('user_id', $userId)
                    ->where('session_type', 'diagnostic')
                    ->delete();
                session()->forget('diagnostic_scenario_ids');
            } else {
                return redirect()->route('dashboard')
                    ->with('info', 'You have already completed the diagnostic.');
            }
        }

        // Try to resume an in-progress diagnostic of exactly 5 questions.
        $ids       = session('diagnostic_scenario_ids');
        $questions = collect();

        if (is_array($ids) && count($ids) === 5) {
            $questions = Scenario::whereIn('id', $ids)->get()
                ->sortBy(fn ($q) => array_search($q->id, $ids))
                ->values();
        }

        // Pick a balanced, multi-modal set of 5 scenarios (URL, Email, SMS)
        if ($questions->count() !== 5) {
            $questions = $this->getBalancedDiagnosticScenarios();
            session(['diagnostic_scenario_ids' => $questions->pluck('id')->toArray()]);
        }

        // Skip questions the user already answered in this diagnostic run.
        $answeredIds = LearningRecord::where('user_id', $userId)
            ->where('session_type', 'diagnostic')
            ->whereIn('scenario_id', $questions->pluck('id'))
            ->pluck('scenario_id')
            ->toArray();

        $remainingQuestions = $questions->reject(fn ($q) => in_array($q->id, $answeredIds))->values();

        // Safety net: if all 5 are answered, mark complete now.
        if ($remainingQuestions->isEmpty()) {
            auth()->user()->update(['diagnostic_completed_at' => now()]);
            session()->forget('diagnostic_scenario_ids');

            return redirect()->route('dashboard')
                ->with('success', 'Diagnostic completed!');
        }

        session(['session_type' => 'diagnostic']);

        // Pass the remaining questions and the total offset for progress tracking
        $answeredCount = count($answeredIds);
        $questions = $remainingQuestions;

        return view('learning.diagnostic', compact('questions', 'answeredCount'));
    }

    /**
     * Retrieve a balanced 5-question multi-modal diagnostic set:
     * 2 URL scenarios (1 phishing, 1 legit),
     * 2 Email scenarios (1 phishing, 1 legit),
     * 1 SMS scenario (phishing or legit).
     */
    protected function getBalancedDiagnosticScenarios(): \Illuminate\Support\Collection
    {
        // 1. Fetch 2 URL Scenarios (1 phishing, 1 legitimate)
        $urlPhish = Scenario::where('type', 'URL / Web Phishing')
            ->where('status', 'approved')
            ->where('is_phishing', true)
            ->inRandomOrder()
            ->first();

        $urlLegit = Scenario::where('type', 'URL / Web Phishing')
            ->where('status', 'approved')
            ->where('is_phishing', false)
            ->inRandomOrder()
            ->first();

        $urls = collect([$urlPhish, $urlLegit])->filter();
        if ($urls->count() < 2) {
            $moreUrls = Scenario::where('type', 'URL / Web Phishing')
                ->where('status', 'approved')
                ->whereNotIn('id', $urls->pluck('id'))
                ->inRandomOrder()
                ->take(2 - $urls->count())
                ->get();
            $urls = $urls->concat($moreUrls);
        }

        // 2. Fetch 2 Email Scenarios (1 phishing, 1 legitimate)
        $emailPhish = Scenario::where('type', 'Email Phishing')
            ->where('status', 'approved')
            ->where('is_phishing', true)
            ->inRandomOrder()
            ->first();

        $emailLegit = Scenario::where('type', 'Email Phishing')
            ->where('status', 'approved')
            ->where('is_phishing', false)
            ->inRandomOrder()
            ->first();

        $emails = collect([$emailPhish, $emailLegit])->filter();
        if ($emails->count() < 2) {
            $moreEmails = Scenario::where('type', 'Email Phishing')
                ->where('status', 'approved')
                ->whereNotIn('id', $emails->pluck('id'))
                ->inRandomOrder()
                ->take(2 - $emails->count())
                ->get();
            $emails = $emails->concat($moreEmails);
        }

        // 3. Fetch 1 SMS Scenario
        $sms = Scenario::where('type', 'SMS Phishing')
            ->where('status', 'approved')
            ->inRandomOrder()
            ->take(1)
            ->get();

        $selected = $urls->concat($emails)->concat($sms);

        // Fallback fill to guarantee exactly 5 scenarios
        if ($selected->count() < 5) {
            $more = Scenario::where('status', 'approved')
                ->whereNotIn('id', $selected->pluck('id'))
                ->inRandomOrder()
                ->take(5 - $selected->count())
                ->get();
            $selected = $selected->concat($more);
        }

        return $selected->shuffle()->values();
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