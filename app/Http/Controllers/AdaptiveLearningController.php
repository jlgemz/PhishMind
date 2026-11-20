<?php

namespace App\Http\Controllers;

use App\Models\LearningRecord;
use App\Models\Scenario;
use App\Services\RefresherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdaptiveLearningController extends Controller
{
    protected $refresher;

    public function __construct(RefresherService $refresher)
    {
        $this->refresher = $refresher;
    }

    public function selectScenario()
    {
        $userId = auth()->id();

        $refresherDue = $this->refresher->isRefresherDue($userId);

        if ($this->refresher->needsDiagnostic($userId)) {
            return redirect()->route('diagnostic.start')
                ->with('info', 'Take a short diagnostic to personalize your practice.');
        }

        $urlScenario    = Scenario::where('type', 'URL / Web Phishing')->where('status', 'approved')->inRandomOrder()->first();
        $emailScenario  = Scenario::where('type', 'Email Phishing')->where('status', 'approved')->inRandomOrder()->first();
        $smsScenario    = Scenario::where('type', 'SMS Phishing')->where('status', 'approved')->inRandomOrder()->first();

        // Safety fallback: ensure no modality is ever missing
        if (!$emailScenario) {
            $emailScenario = Scenario::create([
                'type' => 'Email Phishing',
                'content' => "Subject: URGENT: Campus IT Security Notice - Re-verify Your Account\n\nDear Student,\n\nOur system detected multiple unauthorized login attempts to your student email account. To prevent immediate account suspension, you must re-verify your identity and security settings within 24 hours at the following university portal:\n\nhttps://portal-university-auth.com/verify-session\n\nFailure to verify will result in permanent revocation of your campus single sign-on access.\n\nCampus IT Services & Information Security",
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Uses severe artificial urgency (suspension within 24 hours) and directs users to an external unauthorized domain (portal-university-auth.com) instead of the official institution portal.',
            ]);
        }

        if (!$urlScenario) {
            $urlScenario = Scenario::create([
                'type' => 'URL / Web Phishing',
                'content' => 'https://university-student-portal.security-update.net/login',
                'is_phishing' => true,
                'indicator' => 'deceptive_url',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Uses a deceptive domain suffix designed to imitate the official campus portal.',
            ]);
        }

        if (!$smsScenario) {
            $smsScenario = Scenario::create([
                'type' => 'SMS Phishing',
                'content' => 'Campus Alert: Your student financial aid refund of $1,420.00 is ready. Confirm your bank details to release payment: https://campus-aid-refund.xyz/verify',
                'is_phishing' => true,
                'indicator' => 'urgency',
                'is_diagnostic' => true,
                'status' => 'approved',
                'explanation' => 'Unsolicited financial SMS with external shortlink lure.',
            ]);
        }

        return view('learning.select', compact(
            'urlScenario',
            'emailScenario',
            'smsScenario',
            'refresherDue'
        ));
    }

    public function start(Scenario $scenario)
    {
        return view('learning.start', compact('scenario'));
    }

    /**
     * Fetch another random scenario of the same type for continuous practice.
     */
    public function nextExample(Scenario $scenario)
    {
        $next = Scenario::where('type', $scenario->type)
            ->where('status', 'approved')
            ->where('id', '!=', $scenario->id)
            ->inRandomOrder()
            ->first();

        if (!$next) {
            return redirect()->route('practice.select')
                ->with('info', 'No more scenarios available for this type.');
        }

        return redirect()->route('learning.start', $next->id);
    }

    public function submit(Request $request)
    {
        @set_time_limit(120);

        $validated = $request->validate([
            'scenario_id'       => 'required|exists:scenarios,id',
            'student_decision'  => 'required|in:0,1',
            'student_reasoning' => 'required|string|min:5',
            'session_type'      => 'nullable|string|in:diagnostic,refresher,practice',
        ]);

        $scenario = Scenario::findOrFail($validated['scenario_id']);

        try {
            $mlUrl = rtrim(config('services.ml_service.url', 'http://127.0.0.1:8001'), '/');
            $response = Http::timeout(35)->post("{$mlUrl}/adaptive/evaluate", [
                'url'               => $scenario->content,
                'scenario_type'     => $scenario->type,
                'student_decision'  => (int) $validated['student_decision'],
                'student_reasoning' => $validated['student_reasoning'],
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
        $confidence      = max(0.05, min(0.98, $confidence));
        $isMastered      = ((bool) $scenario->is_phishing === (bool) $validated['student_decision']);

        $record = LearningRecord::create([
            'user_id'              => auth()->id(),
            'scenario_id'          => $scenario->id,
            'scenario_url'         => $scenario->content, // 👈 Copies the entire email body here
            'model_prediction'     => $modelPrediction,
            'student_decision'     => (bool) $validated['student_decision'],
            'model_confidence'     => round($confidence * 100),
            'shap_risk_indicators' => $mlResult['shap_risk_indicators'] ?? [],
            'misconceptions'       => $mlResult['misconceptions'] ?? [],
            'targeted_practice'    => $mlResult['targeted_practice'] ?? [],
            'llm_explanation'      => $mlResult['explanation'] ?? 'The AI explanation could not be generated.',
            'mastery_achieved'     => $isMastered,
            'session_type'         => $validated['session_type'] ?? 'practice',
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
            'success'  => true,
            'redirect' => route('learning.result', $record->id)
        ]);
    }

    public function result(LearningRecord $record)
    {
        if ($record->user_id !== auth()->id()) abort(403);
        
        // Load the relationship so we can use the scenario type for the Next Example button
        $record->load('scenario');
        
        return view('learning.result', compact('record'));
    }

    public function retry(LearningRecord $record)
    {
        if ($record->user_id !== auth()->id()) abort(403);
        return view('learning.retry', compact('record'));
    }

    private function getMockMLResponse($scenario, $decision)
    {
        $isCorrect = ((bool) $scenario->is_phishing === (bool) $decision);

        return [
            'prediction'           => $scenario->is_phishing,
            'confidence'           => 0.78,
            'mastery_achieved'     => $isCorrect,
            'shap_risk_indicators' => [
                ['feature' => 'Domain differs from official university domain', 'value' => '+0.34'],
                ['feature' => 'Credential request', 'value' => '+0.25'],
                ['feature' => 'Urgent account-expiry language', 'value' => '+0.18'],
                ['feature' => 'HTTPS connection', 'value' => '-0.07'],
            ],
            'misconceptions' => !$isCorrect ? [
                [
                    'name'        => 'HTTPS means the site is safe',
                    'description' => 'HTTPS protects data while it travels to a site. It does not prove that the site belongs to the university.'
                ]
            ] : [],
            'targeted_practice' => !$isCorrect ? [
                'Review the domain spelling carefully.',
                'Check if the sender email matches the official domain.'
            ] : ['Keep up the good work!'],
            'explanation' => 'The Random Forest model identified structural risk patterns. This is a fallback explanation because the Python service is offline.',
        ];
    }
}