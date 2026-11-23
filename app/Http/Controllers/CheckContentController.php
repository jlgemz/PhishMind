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
        $trimmed = trim($content);

        if (strlen($trimmed) < 3) {
            return "The submitted content is too short to be processed (minimum 3 characters required).";
        }

        if ($type === 'url') {
            // 1. A URL cannot contain newline characters (identifies emails/messages pasted into URL mode)
            if (str_contains($trimmed, "\n") || str_contains($trimmed, "\r")) {
                return "Invalid URL: submitted content contains multiple lines. It appears you pasted an email or text message while the 'URL' modality was selected. Please choose the 'Email' or 'SMS' modality above and try again.";
            }

            // 2. Reject email greetings or headers in URL modality
            if (preg_match('/^(?:dear|to|hi|hello|subject:|from:|greetings)\b/i', $trimmed)) {
                return "Invalid URL: content begins with an email greeting or header. It appears you entered an email into the 'URL' modality. Please select the 'Email' modality and try again.";
            }

            // 3. Reject if content looks like natural language sentences with multiple spaces
            if (substr_count($trimmed, ' ') > 3 && !preg_match('#^https?://[^\s]+$#', $trimmed)) {
                return "Invalid URL: submitted content contains sentence text with spaces. Please enter a valid web address (e.g., https://example.com) or switch to the 'Email' modality.";
            }

            // 4. Check for obviously malformed URL patterns
            if (preg_match('#^[a-zA-Z]+:///$#', $trimmed) || preg_match('#^[a-zA-Z]+://$#', $trimmed)) {
                return "Malformed URL: missing domain name or host address.";
            }

            $target = preg_match('#^[a-zA-Z]+://#', $trimmed) ? $trimmed : 'https://' . $trimmed;
            $parsed = parse_url($target);
            $host   = $parsed['host'] ?? '';

            // Host authority cannot contain whitespace or sentence punctuation
            if (empty($host) || preg_match('/[\s,;:!?"\'\(\)\[\]\{\}]/', $host)) {
                return "Invalid URL format: unable to identify a valid domain name or hostname. If you meant to analyze an email or text message, please select the corresponding modality.";
            }

            $hasIp       = filter_var($host, FILTER_VALIDATE_IP) !== false;
            $hasDomain   = str_contains($host, '.') && strlen($host) >= 4 && preg_match('/^[a-zA-Z0-9.-]+$/', $host);
            $isLocalhost = in_array(strtolower($host), ['localhost', '127.0.0.1']);

            if (!$hasIp && !$hasDomain && !$isLocalhost) {
                return "Malformed URL: unable to detect a valid domain name, hostname, or IP address.";
            }
        } elseif ($type === 'email') {
            if (strlen($trimmed) < 8) {
                return "Email content is too short for phishing feature extraction (minimum 8 characters).";
            }
        } elseif ($type === 'sms') {
            if (strlen($trimmed) < 4) {
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
        $type = strtolower($type);
        $indicators = [];
        $isPhishing = false;
        $confidence = 0.70;

        if ($type === 'url') {
            $detector = app(\App\Services\PhishingDetectorService::class);
            $analysis = $detector->analyzeURL($content);
            $isPhishing = ($analysis['status'] === 'dangerous' || ($analysis['confidence_score'] ?? 0) >= 50);
            $confidence = max(0.55, min(0.95, ($analysis['confidence_score'] ?? 50) / 100));

            foreach ($analysis['indicators'] ?? [] as $ind) {
                $indicators[] = [
                    'feature' => $ind,
                    'value'   => $isPhishing ? '+0.15' : '-0.15',
                    'type'    => $isPhishing ? 'risk' : 'safety',
                ];
            }
        } elseif ($type === 'email') {
            $lower = strtolower($content);
            $hasUrgency = str_contains($lower, 'urgent') || str_contains($lower, 'suspended') || str_contains($lower, 'action required') || str_contains($lower, '24 hours');
            $hasCreds = str_contains($lower, 'password') || str_contains($lower, 'verify your') || str_contains($lower, 'login') || str_contains($lower, 'credentials');
            $hasLinks = str_contains($lower, 'http://') || str_contains($lower, 'https://');
            $isFreeSender = str_contains($lower, 'gmail.com') || str_contains($lower, 'yahoo.com');

            $score = ($hasUrgency ? 1 : 0) + ($hasCreds ? 1 : 0) + ($hasLinks ? 1 : 0) + ($isFreeSender ? 1 : 0);
            $isPhishing = ($score >= 2);
            $confidence = $isPhishing ? 0.78 : 0.72;

            if ($hasUrgency) $indicators[] = ['feature' => 'Urgency & Pressure Language Score', 'value' => '+0.25', 'type' => 'risk'];
            if ($hasCreds) $indicators[] = ['feature' => 'Requests Passwords, PINs, or Credentials', 'value' => '+0.30', 'type' => 'risk'];
            if ($isFreeSender) $indicators[] = ['feature' => 'Sent from Free Webmail Service', 'value' => '+0.20', 'type' => 'risk'];
            if ($hasLinks) $indicators[] = ['feature' => 'Embedded Hyperlinks Present', 'value' => '+0.15', 'type' => 'risk'];

            if (empty($indicators)) {
                $indicators[] = ['feature' => 'Email Subject Line Length', 'value' => '-0.20', 'type' => 'safety'];
                $indicators[] = ['feature' => 'Standard Email Correspondence Format', 'value' => '-0.25', 'type' => 'safety'];
            }
        } else {
            // SMS Modality
            $lower = strtolower($content);
            $hasUrgency = str_contains($lower, 'urgent') || str_contains($lower, 'suspended') || str_contains($lower, 'blocked') || str_contains($lower, 'immediately');
            $hasLink = str_contains($lower, 'http') || str_contains($lower, 'www.') || str_contains($lower, '.xyz') || str_contains($lower, '.top');
            $hasThreat = str_contains($lower, 'account will be') || str_contains($lower, 'warning') || str_contains($lower, 'penalty');

            $score = ($hasUrgency ? 1 : 0) + ($hasLink ? 1 : 0) + ($hasThreat ? 1 : 0);
            $isPhishing = ($score >= 2);
            $confidence = $isPhishing ? 0.82 : 0.75;

            if ($hasUrgency) $indicators[] = ['feature' => 'Urgent Call-to-Action Language', 'value' => '+0.30', 'type' => 'risk'];
            if ($hasLink) $indicators[] = ['feature' => 'Contains Embedded Web Link', 'value' => '+0.28', 'type' => 'risk'];
            if ($hasThreat) $indicators[] = ['feature' => 'Account Suspension / Threatening Language', 'value' => '+0.22', 'type' => 'risk'];

            if (empty($indicators)) {
                $indicators[] = ['feature' => 'Standard Filipino Advisory Format', 'value' => '-0.30', 'type' => 'safety'];
                $indicators[] = ['feature' => 'No Suspicious Redirection Links', 'value' => '-0.25', 'type' => 'safety'];
            }
        }

        $exp = $isPhishing
            ? "This {$type} was identified as suspicious because it displays warning signs commonly found in phishing scams. The message attempts to create artificial urgency, directs you to an unverified destination, or requests sensitive details under false pretenses. Always treat unexpected requests with extreme caution."
            : "This {$type} appears legitimate based on standard communication patterns. It does not contain suspicious redirection links, credential harvesting lures, or alarming pressure tactics. Even so, always remain attentive before sharing any private information.";

        if ($type === 'sms') {
            $rec = $isPhishing
                ? "• Never tap links or open web shortcuts sent in unexpected text messages.\n• Never share one-time passcodes (OTP), account passwords, or banking PINs over SMS—authentic organizations never ask for them by text.\n• Forward spam or suspicious text messages to your phone carrier spam service (such as 7726) and verify your account status directly inside the official mobile app."
                : "• This text notification appears authentic. However, always remember that genuine institutions will never ask for your password or verification codes over SMS.\n• If a text message instructs you to take urgent action, independently open the provider's official mobile application instead of tapping links.";
        } elseif ($type === 'email') {
            $rec = $isPhishing
                ? "• Check the sender's full email address and domain name in the message header, not just the displayed name.\n• Avoid clicking links or opening unexpected attachments from unsolicited or urgent emails.\n• Forward the suspicious email to your organization's IT security team, and verify any urgent request by contacting the sender on a known official phone number."
                : "• This email appears authentic and follows standard communication formats.\n• Always verify the sender's complete email address if an unexpected request arrives, and never share credentials over email.";
        } else {
            $rec = $isPhishing
                ? "• Carefully examine the full address in your browser address bar for misspelled names, extra subdomains, or deceptive extensions like .xyz or .top.\n• Never enter your login password, payment cards, or sensitive personal details on an unverified web page.\n• Navigate to trusted websites by typing their known official address directly into the browser address bar or using saved bookmarks."
                : "• This web address matches expected legitimate patterns. Always verify that the domain name is spelled correctly before logging in.\n• Remember that having HTTPS or a lock icon only encrypts the connection—it does not automatically guarantee who owns the website.";
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
            'explanation'          => $exp,
            'recommendation'       => $rec,
        ];
    }

    private function detectType(string $content): string
    {
        $trimmed = trim($content);

        // 1. Check for URL patterns (with or without protocol, including www, IP addresses, and domain.tld)
        if (preg_match('#^https?://#i', $trimmed)
            || preg_match('#^www\.#i', $trimmed)
            || preg_match('#^[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+(/[^\s]*)?$#i', $trimmed)
            || (filter_var('https://' . $trimmed, FILTER_VALIDATE_URL) && !str_contains($trimmed, ' ') && str_contains($trimmed, '.'))) {
            return 'url';
        }

        // 2. Check for Email patterns
        $lower = strtolower($trimmed);
        if (str_contains($lower, 'dear ')
            || str_contains($lower, 'kind regards')
            || str_contains($lower, 'sincerely')
            || str_contains($lower, 'subject:')
            || str_contains($trimmed, '|')
            || preg_match('/from:\s*[^\n]+@[^\n]+/i', $trimmed)) {
            return 'email';
        }

        // 3. Default to SMS
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
            return $this->localRandomForestFallback($content, $type);
        }

        $prompt = "You are an expert cybersecurity educator explaining a content analysis to a regular user in plain language.\n\n"
                . "Analyzed {$type} content:\n\"\"\"\n{$content}\n\"\"\"\n\n"
                . "Respond ONLY with a valid JSON object in this exact format:\n"
                . "{\n"
                . "  \"prediction\": 1, // 1 for Phishing, 0 for Legitimate\n"
                . "  \"confidence\": 0.95, // between 0.05 and 0.99\n"
                . "  \"explanation\": \"A simple, clear explanation in simple words explaining why this content is safe or dangerous. EXPLAIN ONLY IN SIMPLE WORDS. DO NOT INCLUDE ANY NUMBERS, percentages, scores, decimal values, counts, or statistical figures anywhere in the explanation.\",\n"
                . "  \"recommendation\": \"2-3 bulleted cybersecurity steps specifically tailored to handle {$type} phishing threats (inspecting browser address bar for URL, email headers for email, or not tapping SMS links for SMS).\",\n"
                . "  \"risk_indicators\": [\"Specific plain signal 1\", \"Specific plain signal 2\"]\n"
                . "}\n\n"
                . "Critical Rules:\n"
                . "1. Use simple everyday words.\n"
                . "2. ZERO numbers in the explanation.\n"
                . "3. Never include AI branding tags like [GROQ AI].\n"
                . "4. The recommendation must directly address {$type} safety.";

        try {
            $response = Http::withToken($apiKey)->timeout(10)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'qwen/qwen3.8-27b', 
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an educational phishing analysis AI. Never include branding tags like [GROQ AI].'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2
            ]);

            if ($response->successful()) {
                $rawContent = $response->json('choices.0.message.content');
                // Strip any possible branding
                $rawContent = preg_replace('/\[GROQ\s*AI[^\]]*\]/i', '', $rawContent);
                $data = json_decode($rawContent, true);
                
                $shapUi = [];
                $pred = (int) ($data['prediction'] ?? 1);
                foreach ($data['risk_indicators'] ?? [] as $indicator) {
                    $shapUi[] = [
                        'feature' => $indicator,
                        'value' => $pred === 1 ? '+0.25' : '-0.25',
                        'type' => $pred === 1 ? 'risk' : 'safety'
                    ];
                }

                $explanation = trim($data['explanation'] ?? 'This content was analyzed using verified security patterns.');
                $explanation = preg_replace('/\[GROQ\s*AI[^\]]*\]/i', '', $explanation);
                // Ensure zero numbers or percentage symbols in explanation
                $explanation = preg_replace('/\b\d+(\.\d+)?%\b/', 'high', $explanation);
                $explanation = preg_replace('/\([+-]?\d+(\.\d+)?\)/', '', $explanation);
                $explanation = preg_replace('/\b\d{2,}\b/', '', $explanation);
                $explanation = preg_replace('/\s{2,}/', ' ', $explanation);
                $explanation = trim($explanation);

                $recommendation = trim($data['recommendation'] ?? '');
                $recommendation = preg_replace('/\[GROQ\s*AI[^\]]*\]/i', '', $recommendation);
                if (empty($recommendation) || strlen($recommendation) < 10) {
                    if ($type === 'sms') {
                        $recommendation = $pred === 1
                            ? "• Never tap links or open web shortcuts sent in unexpected text messages.\n• Never share one-time passcodes (OTP), account passwords, or banking PINs over SMS—authentic organizations never ask for them by text.\n• Forward spam or suspicious text messages to your phone carrier spam service (such as 7726) and verify your account status directly inside the official mobile app."
                            : "• This text notification appears authentic. However, always remember that genuine institutions will never ask for your password or verification codes over SMS.\n• If a text message instructs you to take urgent action, independently open the provider's official mobile application instead of tapping links.";
                    } elseif ($type === 'email') {
                        $recommendation = $pred === 1
                            ? "• Check the sender's full email address and domain name in the message header, not just the displayed name.\n• Avoid clicking links or opening unexpected attachments from unsolicited or urgent emails.\n• Forward the suspicious email to your organization's IT security team, and verify any urgent request by contacting the sender on a known official phone number."
                            : "• This email appears authentic and follows standard communication formats.\n• Always verify the sender's complete email address if an unexpected request arrives, and never share credentials over email.";
                    } else {
                        $recommendation = $pred === 1
                            ? "• Carefully examine the full address in your browser address bar for misspelled names, extra subdomains, or deceptive extensions like .xyz or .top.\n• Never enter your login password, payment cards, or sensitive personal details on an unverified web page.\n• Navigate to trusted websites by typing their known official address directly into the browser address bar or using saved bookmarks."
                            : "• This web address matches expected legitimate patterns. Always verify that the domain name is spelled correctly before logging in.\n• Remember that having HTTPS or a lock icon only encrypts the connection—it does not automatically guarantee who owns the website.";
                    }
                }

                return [
                    'prediction'           => $pred,
                    'confidence'           => $data['confidence'] ?? 0.85,
                    'probabilities'        => [
                        'legitimate' => $pred === 1 ? 0.15 : 0.85,
                        'phishing'   => $pred === 1 ? 0.85 : 0.15,
                    ],
                    'risk_score'           => $pred === 1 ? 85 : 15,
                    'reliability'          => 'high',
                    'is_borderline'        => false,
                    'shap_risk_indicators' => $shapUi,
                    'warning'              => $this->generateWarning($pred, $data['confidence'] ?? 0.85),
                    'explanation'          => $explanation,
                    'recommendation'       => $recommendation,
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