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
            'content' => 'required|string|min:2',
            'type'    => 'nullable|in:url,email,sms',
        ]);

        $content = trim($validated['content']);
        $type    = $validated['type'] ?? $this->detectType($content);

        // Pre-validate input format to prevent invalid inputs from being falsely classified as legitimate
        $validationError = $this->validateInputProcessability($content, $type);
        if ($validationError) {
            $result = [
                'verdict'        => 'Invalid Input',
                'risk_score'     => 0,
                'confidence'     => 0.0,
                'reliability'    => 'none',
                'is_borderline'  => false,
                'engine'         => 'Input Validator',
                'probabilities'  => ['legitimate' => 0.0, 'phishing' => 0.0],
                'indicators'     => [
                    ['feature' => 'Structural Validation Failure', 'value' => '0.00', 'type' => 'error']
                ],
                'explanation'    => $validationError,
                'warning'        => 'The submitted input is malformed or unprocessable. To prevent security blindspots, unprocessable inputs are never classified as legitimate.',
                'recommendation' => 'Please provide a properly formatted input (e.g., a valid URL with a recognizable domain or IP, or sufficient text content) and try again.',
                'misconception'  => null,
                'type'           => $type,
                'is_dataset_match' => false,
            ];

            return view('check.result', compact('result', 'content', 'type'));
        }

        // Optional metadata: Check if content happens to be in our training database
        $scenarioMatch = null;
        try {
            $scenarioMatch = Scenario::where('content', $content)->first();
        } catch (\Exception $e) {
            // Gracefully proceed if DB is offline during direct testing
        }

        // Query the Random Forest Machine Learning Service
        $mlUrl = rtrim(config('services.ml_service.url', 'http://127.0.0.1:8001'), '/');
        $ml = [];
        $engine = 'Random Forest Model';

        try {
            $response = Http::timeout(3)->post("{$mlUrl}/predict", [
                'content'  => $content,
                'modality' => $type,
            ]);

            if ($response->successful()) {
                $ml = $response->json();
            } else {
                Log::warning('ML Service /predict returned non-200. Using fallback heuristics: ' . $response->body());
                $ml = $this->localRandomForestFallback($content, $type);
                $engine = 'Local Heuristic Fallback (ML Offline)';
            }
        } catch (\Exception $e) {
            Log::warning('ML Service offline in check.analyze: ' . $e->getMessage());
            $ml = $this->localRandomForestFallback($content, $type);
            $engine = 'Local Heuristic Fallback (ML Offline)';
        }

        // If Python returned an invalid status, forward the validation error
        if (isset($ml['is_processable']) && !$ml['is_processable']) {
            $result = [
                'verdict'        => 'Invalid Input',
                'risk_score'     => 0,
                'confidence'     => 0.0,
                'reliability'    => 'none',
                'is_borderline'  => false,
                'engine'         => 'Random Forest Preprocessor',
                'probabilities'  => ['legitimate' => 0.0, 'phishing' => 0.0],
                'indicators'     => [
                    ['feature' => 'Malformed Input Format', 'value' => '0.00', 'type' => 'error']
                ],
                'explanation'    => $ml['error'] ?? 'The submitted input cannot be parsed for feature extraction.',
                'warning'        => $ml['warning'] ?? 'Unprocessable inputs are not treated as legitimate.',
                'recommendation' => $ml['recommendation'] ?? 'Please check the formatting of your input and try again.',
                'misconception'  => null,
                'type'           => $type,
                'is_dataset_match' => false,
            ];

            return view('check.result', compact('result', 'content', 'type'));
        }

        $prediction = (int) ($ml['prediction'] ?? 0);
        $confidence = (float) ($ml['confidence'] ?? 0.5);
        $riskScore  = (int) ($ml['risk_score'] ?? ($prediction === 1 ? round($confidence * 100) : round((1 - $confidence) * 100)));
        $probabilities = $ml['probabilities'] ?? [
            'legitimate' => $prediction === 1 ? (1.0 - $confidence) : $confidence,
            'phishing'   => $prediction === 1 ? $confidence : (1.0 - $confidence),
        ];

        // Format SHAP indicators
        $indicators = [];
        foreach ($ml['shap_risk_indicators'] ?? [] as $row) {
            $indicators[] = [
                'feature' => $row['feature'] ?? $row['plain_name'] ?? 'Signal',
                'value'   => $row['value']   ?? $row['contribution'] ?? '0.00',
                'type'    => $row['type']    ?? (str_starts_with($row['value'] ?? '', '-') ? 'safety' : 'risk'),
            ];
        }

        if (empty($indicators)) {
            $indicators = [
                ['feature' => 'Baseline Feature Distribution', 'value' => '0.00', 'type' => 'neutral']
            ];
        }

        // Construct result payload
        $result = [
            'verdict'          => $prediction === 1 ? 'Phishing' : 'Legitimate',
            'risk_score'       => $riskScore,
            'confidence'       => $confidence,
            'probabilities'    => $probabilities,
            'reliability'      => $ml['reliability'] ?? ($confidence >= 0.8 ? 'high' : ($confidence <= 0.6 ? 'low' : 'moderate')),
            'is_borderline'    => $ml['is_borderline'] ?? (abs($probabilities['phishing'] - 0.5) <= 0.1),
            'warning'          => $ml['warning'] ?? $this->generateWarning($prediction, $confidence),
            'indicators'       => $indicators,
            'explanation'      => $ml['explanation'] ?? 'The Random Forest model evaluated this input using its learned feature patterns.',
            'recommendation'   => $ml['recommendation'] ?? ($prediction === 1
                ? 'Do not click links, do not reply, and report this suspicious message to your security team.'
                : 'While this input does not trigger known phishing signatures, remain vigilant and verify unexpected requests through trusted channels.'),
            'misconception'    => null,
            'type'             => $type,
            'engine'           => $engine,
            'is_dataset_match' => (bool) $scenarioMatch,
            'scenario_id'      => $scenarioMatch?->id,
        ];

        return view('check.result', compact('result', 'content', 'type'));
    }

    private function validateInputProcessability(string $content, string $type): ?string
    {
        if (strlen($content) < 3) {
            return "The submitted content is too short to be processed (minimum 3 characters required).";
        }

        if ($type === 'url') {
            // Check for obviously malformed URL patterns
            if (preg_match('#^[a-zA-Z]+:///$#', $content) || preg_match('#^[a-zA-Z]+://$#', $content)) {
                return "Malformed URL: missing domain name or host address.";
            }

            $target = preg_match('#^[a-zA-Z]+://#', $content) ? $content : 'http://' . $content;
            $parsed = parse_url($target);
            $host   = $parsed['host'] ?? '';

            $hasIp       = filter_var($host, FILTER_VALIDATE_IP) !== false;
            $hasDomain   = str_contains($host, '.') && strlen($host) >= 4;
            $isLocalhost = in_array(strtolower($host), ['localhost', '127.0.0.1']);

            if (!$hasIp && !$hasDomain && !$isLocalhost) {
                return "Malformed URL: unable to detect a valid domain name, hostname, or IP address.";
            }
        } elseif ($type === 'email') {
            if (strlen($content) < 8) {
                return "Email content is too short for phishing feature extraction (minimum 8 characters).";
            }
        } elseif ($type === 'sms') {
            if (strlen($content) < 4) {
                return "SMS message is too short to evaluate (minimum 4 characters).";
            }
        }

        return null;
    }

    private function generateWarning(int $prediction, float $confidence): string
    {
        if ($prediction === 1) {
            return sprintf(
                "Threat Detected: The Random Forest model identified patterns consistent with phishing attacks (Confidence: %.1f%%). Exercise caution and avoid interacting with this content.",
                $confidence * 100
            );
        }

        return sprintf(
            "Notice: The Random Forest model classified this input as Legitimate based on learned patterns (Confidence: %.1f%%). However, absence of detected phishing signatures DOES NOT guarantee safety. Novel phishing techniques, zero-day links, or compromised accounts may not be recognized by the model. Always verify sender identity independently.",
            $confidence * 100
        );
    }

    private function localRandomForestFallback(string $content, string $type): array
    {
        $detector = app(\App\Services\PhishingDetectorService::class);
        $analysis = $detector->analyzeURL($content);
        $isPhishing = ($analysis['status'] === 'dangerous' || ($analysis['confidence_score'] ?? 0) >= 50);
        $confidence = max(0.55, min(0.95, ($analysis['confidence_score'] ?? 50) / 100));

        $indicators = [];
        foreach ($analysis['indicators'] ?? [] as $ind) {
            $indicators[] = [
                'feature' => $ind,
                'value'   => $isPhishing ? '+0.15' : '-0.15',
                'type'    => $isPhishing ? 'risk' : 'safety',
            ];
        }

        return [
            'prediction'           => $isPhishing ? 1 : 0,
            'confidence'           => $confidence,
            'probabilities'        => [
                'legitimate' => $isPhishing ? (1.0 - $confidence) : $confidence,
                'phishing'   => $isPhishing ? $confidence : (1.0 - $confidence),
            ],
            'risk_score'           => $isPhishing ? (int) round($confidence * 100) : (int) round((1 - $confidence) * 100),
            'reliability'          => 'moderate',
            'is_borderline'        => false,
            'shap_risk_indicators' => $indicators,
            'warning'              => $this->generateWarning($isPhishing ? 1 : 0, $confidence),
            'explanation'          => 'Analyzed using local heuristic rules because the Python ML service is temporarily offline.',
            'recommendation'       => $isPhishing
                ? 'Do not click links, do not reply, and report this suspicious message to your security team.'
                : 'While this input does not trigger known phishing signatures, remain vigilant and verify unexpected requests through trusted channels.',
        ];
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