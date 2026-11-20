<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\LearningRecord;
use App\Services\RefresherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefresherController extends Controller
{
    protected $refresher;

    public function __construct(RefresherService $refresher)
    {
        $this->refresher = $refresher;
    }

    public function start()
    {
        $userId = auth()->id();
        $questions = $this->refresher->getRefresherQuestions($userId, 5);

        if ($questions->isEmpty()) {
            return redirect()->route('practice.select')
                ->with('error', 'No refresher scenarios available.');
        }

        session([
            'session_type' => 'refresher',
            'refresher_scenario_ids' => $questions->pluck('id')->toArray(),
        ]);

        return view('learning.refresher', compact('questions'));
    }

    public function submit(Request $request)
    {
        @set_time_limit(120);

        $validated = $request->validate([
            'scenario_id'       => 'required|exists:scenarios,id',
            'student_decision'  => 'required|in:0,1',
            'student_reasoning' => 'nullable|string|max:1000',
        ]);

        $scenario = Scenario::findOrFail($validated['scenario_id']);

        try {
            $mlUrl = rtrim(config('services.ml_service.url', 'http://127.0.0.1:8001'), '/');
            $response = Http::timeout(35)->post("{$mlUrl}/adaptive/evaluate", [
                'url'               => $scenario->content,
                'scenario_type'     => $scenario->type,
                'student_decision'  => (int) $validated['student_decision'],
                'student_reasoning' => $validated['student_reasoning'] ?? 'Refresher response',
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

        $isMastered = ((bool) $scenario->is_phishing === (bool) $validated['student_decision']);

        LearningRecord::create([
            'user_id'              => auth()->id(),
            'scenario_id'          => $scenario->id,
            'scenario_url'         => $scenario->content,
            'model_prediction'     => (bool) ($mlResult['prediction'] ?? $scenario->is_phishing),
            'student_decision'     => (bool) $validated['student_decision'],
            'model_confidence'     => round(($mlResult['confidence'] ?? 0.86) * 100),
            'shap_risk_indicators' => $mlResult['shap_risk_indicators'] ?? [],
            'misconceptions'       => $mlResult['misconceptions'] ?? [],
            'targeted_practice'    => $mlResult['targeted_practice'] ?? [],
            'llm_explanation'      => $mlResult['explanation'] ?? 'No explanation available.',
            'mastery_achieved'     => $isMastered,
            'session_type'         => 'refresher',
            'attempts'             => 1,
        ]);

        if ($scenario->indicator) {
            $this->refresher->updateProfile(
                auth()->id(),
                $scenario->indicator,
                $isMastered
            );
        }

        return response()->json([
            'success' => true,
            'correct' => $isMastered,
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
                ['feature' => 'Domain differs', 'value' => '+0.34'],
            ],
            'misconceptions'       => [],
            'targeted_practice'    => [],
            'explanation'          => 'Fallback explanation.',
        ];
    }
}