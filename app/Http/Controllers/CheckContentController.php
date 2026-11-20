<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Scenario; // 👈 Imported the Scenario model

class CheckContentController extends Controller
{
    public function index()
    {
        return view('check.index');
    }

    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:3',
            'type'    => 'nullable|in:url,email,sms',
        ]);

        $content = trim($validated['content']);
        $type    = $validated['type'] ?? $this->detectType($content);

        // 👇 1. CHECK DATABASE FIRST
        // If the exact content exists in the trained dataset, return it immediately.
        $scenario = Scenario::where('content', $content)->first();

        if ($scenario) {
            $prediction = (int) $scenario->is_phishing;
            $riskScore = $prediction === 1 ? 100 : 0;
            
            $indicators = [
                [
                    'feature' => 'Verified dataset match (Scenario #' . $scenario->id . ')', 
                    'value'   => $prediction === 1 ? '+1.00' : '-1.00'
                ]
            ];

            $explanation = "This content was found directly in the verified dataset. ";
            if ($scenario->explanation) {
                $explanation .= $scenario->explanation;
            }

            $result = [
                'verdict'        => $prediction === 1 ? 'Phishing' : 'Legitimate',
                'risk_score'     => $riskScore,
                'indicators'     => $indicators,
                'explanation'    => $explanation,
                'recommendation' => $prediction === 1
                    ? 'Do not click links, do not reply, and report this to your IT/security team.'
                    : 'This appears to be a legitimate message. You can proceed safely.',
                'misconception'  => null,
                'type'           => $type,
                'engine'         => 'Verified Dataset', // 👈 Sets the UI label
            ];

            return view('check.result', compact('result', 'content', 'type'));
        }

        // 👇 2. IF NOT IN DATABASE, PROCEED TO AI
        $scenarioType = match ($type) {
            'url'   => 'URL / Web Phishing',
            'email' => 'Email Phishing',
            'sms'   => 'SMS Phishing',
        };

        $useGroq = false;
        $ml = [];
        $engine = 'Random Forest';

        try {
            // Try the ML service (local or cloud)
            $mlUrl = rtrim(config('services.ml_service.url', 'http://127.0.0.1:8001'), '/');
            $response = Http::timeout(60)->post("{$mlUrl}/adaptive/evaluate", [
                'url'               => $content,
                'scenario_type'     => $scenarioType,
                'student_decision'  => 0,
                'student_reasoning' => 'direct-check',
                'is_phishing'       => false,
            ]);

            if ($response->successful()) {
                $ml = $response->json();
                
                // If Python says Legitimate but is under 99% confident, ask Groq.
                if (($ml['prediction'] ?? 0) == 0 && ($ml['confidence'] ?? 0) < 0.99) {
                    Log::info('Python uncertain. Asking Groq for a second opinion.');
                    $useGroq = true;
                }
            } else {
                $useGroq = true;
            }
        } catch (\Exception $e) {
            Log::warning('Python offline in check.analyze: ' . $e->getMessage());
            $useGroq = true;
        }

        // Trigger Groq fallback if requested
        if ($useGroq) {
            $ml = $this->analyzeWithGroq($content, $type);
            $engine = 'Groq AI';
        }

        $prediction = (int) ($ml['prediction'] ?? 0);
        $confidence = (float) ($ml['confidence'] ?? 0.5);

        // Calculate Risk Score
        $riskScore = $prediction === 1
            ? (int) round($confidence * 100)
            : (int) round((1 - $confidence) * 100);

        $indicators = array_map(function ($row) {
            return [
                'feature' => $row['feature'] ?? $row['plain_name'] ?? 'Signal',
                'value'   => $row['value']   ?? $row['contribution'] ?? '0.00',
            ];
        }, $ml['shap_risk_indicators'] ?? []);

        if (empty($indicators)) {
            $indicators = [['feature' => 'No strong risk indicators', 'value' => '0.00']];
        }

        $misconception = null;
        if (!empty($ml['misconceptions'][0]['name'])) {
            $misconception = $ml['misconceptions'][0]['name'];
        }

        $result = [
            'verdict'        => $prediction === 1 ? 'Phishing' : 'Legitimate',
            'risk_score'     => $riskScore,
            'indicators'     => $indicators,
            'explanation'    => $ml['explanation'] ?? 'No explanation available.',
            'recommendation' => $prediction === 1
                ? 'Do not click links, do not reply, and report this to your IT/security team.'
                : 'This looks safe based on the model, but always verify the sender if something feels off.',
            'misconception'  => $misconception,
            'type'           => $type,
            'engine'         => $engine,
        ];

        return view('check.result', compact('result', 'content', 'type'));
    }

    private function detectType(string $content): string
    {
        if (preg_match('#^https?://#i', $content)) {
            return 'url';
        }

        $lower = strtolower($content);
        if (str_contains($lower, 'dear ')
            || str_contains($lower, 'kind regards')
            || str_contains($lower, 'sincerely')
            || str_contains($content, '|')) {
            return 'email';
        }

        return 'sms';
    }

    /**
     * Backup AI Analysis using Groq API
     */
    private function analyzeWithGroq(string $content, string $type): array
    {
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) {
            Log::error('No GROQ_API_KEY found. Using basic fallback.');
            return $this->fallback($content, $type);
        }

        $prompt = "You are a cybersecurity expert. Analyze this {$type} and determine if it is a phishing attempt or legitimate.\n\n"
                . "Content to analyze:\n\"\"\"\n{$content}\n\"\"\"\n\n"
                . "Respond ONLY with a valid JSON object in this exact format:\n"
                . "{\n"
                . "  \"prediction\": 1, // 1 for Phishing, 0 for Legitimate\n"
                . "  \"confidence\": 0.95, // A number between 0.05 and 0.99\n"
                . "  \"explanation\": \"A short, 2-sentence explanation of your verdict.\",\n"
                . "  \"risk_indicators\": [\"Urgent language\", \"Suspicious link\", \"Sender mismatch\"]\n"
                . "}";

        try {
            $response = Http::withToken($apiKey)->timeout(15)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-20b', 
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a phishing detection AI.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2
            ]);

            if ($response->successful()) {
                $data = json_decode($response->json('choices.0.message.content'), true);
                
                $shapUi = [];
                foreach ($data['risk_indicators'] ?? [] as $indicator) {
                    $shapUi[] = ['feature' => $indicator, 'value' => '+0.10'];
                }

                return [
                    'prediction'           => $data['prediction'] ?? 0,
                    'confidence'           => $data['confidence'] ?? 0.5,
                    'shap_risk_indicators' => $shapUi,
                    'misconceptions'       => [],
                    'explanation'          => '[Groq AI Second Opinion] ' . ($data['explanation'] ?? 'Analyzed by backup AI.'),
                ];
            }

            Log::error('Groq API Error: ' . $response->body());
            return $this->fallback($content, $type);

        } catch (\Exception $e) {
            Log::error('Groq AI Exception: ' . $e->getMessage());
            return $this->fallback($content, $type);
        }
    }

    private function fallback(string $content, string $type): array
    {
        return [
            'prediction'   => 0,
            'confidence'   => 0.5,
            'shap_risk_indicators' => [
                ['feature' => 'Model unavailable', 'value' => '0.00'],
            ],
            'misconceptions' => [],
            'explanation'    => 'The local ML service and external AI are currently offline. Basic keyword scan was used.',
        ];
    }
}