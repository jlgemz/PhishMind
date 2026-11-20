<?php

namespace App\Http\Controllers;

use App\Models\URLScan;
use App\Services\MLService;
use App\Services\EmailPhishingDetector;
use App\Services\ImagePhishingDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailLinkController extends Controller
{
    protected $mlService;
    protected $emailDetector;
    protected $imageDetector;

    public function __construct(
        MLService $mlService,
        EmailPhishingDetector $emailDetector,
        ImagePhishingDetector $imageDetector
    ) {
        $this->mlService = $mlService;
        $this->emailDetector = $emailDetector;
        $this->imageDetector = $imageDetector;
    }

    public function checkLink(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'sender_email' => 'nullable|email|max:255',
            'email_subject' => 'nullable|string|max:500',
            'image' => 'nullable|array',
            'image.img_src' => 'nullable|url',
            'image.alt_text' => 'nullable|string|max:500',
            'image.img_title' => 'nullable|string|max:500'
        ]);

        try {
            $url = $validated['url'];
            $senderEmail = $validated['sender_email'] ?? null;
            $emailSubject = $validated['email_subject'] ?? null;
            $imageData = $validated['image'] ?? null;

            // Check cache
            $existingScan = URLScan::where('url', $url)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if ($existingScan) {
                return $this->formatResponse($existingScan, true);
            }

            // 1. ML analysis
            $mlResult = $this->mlService->analyze($url);

            // 2. Email analysis
            $emailAnalysis = $this->emailDetector->analyzeEmail($url, $senderEmail, $emailSubject, $imageData);

            // 3. Image analysis
            $imageAnalysis = null;
            if ($imageData && isset($imageData['img_src'])) {
                $imageAnalysis = $this->imageDetector->analyzeImage($imageData['img_src'], $url);
            }

            // 4. Calculate combined score
            $mlScore = isset($mlResult['prediction']) ?
                ($mlResult['prediction'] === 1 ? $mlResult['confidence'] * 100 : 0) : 0;
            $emailScore = $emailAnalysis['risk_score'] ?? 0;
            $imageScore = $imageAnalysis['risk_score'] ?? 0;

            // Weighted: 50% ML, 30% Email, 20% Image
            $combinedScore = ($mlScore * 0.5) + ($emailScore * 0.3) + ($imageScore * 0.2);

            // 5. Determine status
            $status = 'safe';
            if ($combinedScore >= 70) {
                $status = 'dangerous';
            } elseif ($combinedScore >= 40) {
                $status = 'suspicious';
            }

            // 6. Compile indicators
            $indicators = $mlResult['risk_indicators'] ?? [];
            if (isset($emailAnalysis['indicators'])) {
                $indicators = array_merge($indicators, $emailAnalysis['indicators']);
            }
            if ($imageAnalysis && isset($imageAnalysis['indicators'])) {
                $indicators = array_merge($indicators, $imageAnalysis['indicators']);
            }
            $indicators = array_unique(array_filter($indicators));

            // 7. Save
            $scan = URLScan::create([
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST) ?? $url,
                'status' => $status,
                'confidence_score' => $combinedScore,
                'scan_results' => $indicators,
                'sender_email' => $senderEmail,
                'email_subject' => $emailSubject,
                'detection_source' => 'email',
                'image_url' => $imageData['img_src'] ?? null,
                'image_alt' => $imageData['alt_text'] ?? null,
                'email_metadata' => [
                    'email_analysis' => $emailAnalysis,
                    'image_analysis' => $imageAnalysis,
                    'image_data' => $imageData
                ],
                'metadata' => [
                    'ml_result' => $mlResult,
                    'scanned_at' => now()->toDateTimeString()
                ]
            ]);

            return $this->formatResponse($scan, false);

        } catch (\Exception $e) {
            Log::error('Email link check failed: ' . $e->getMessage(), [
                'url' => $validated['url'] ?? null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to analyze email link: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function formatResponse(URLScan $scan, bool $cached): array
    {
        return [
            'status' => $scan->status,
            'confidence' => $scan->confidence_score,
            'url' => $scan->url,
            'domain' => $scan->domain,
            'indicators' => $scan->scan_results,
            'is_phishing' => $scan->status === 'dangerous',
            'message' => $this->getStatusMessage($scan->status),
            'cached' => $cached,
            'email_analysis' => $this->getEmailAnalysis($scan),
            'image_analysis' => $this->getImageAnalysis($scan)
        ];
    }

    protected function getEmailAnalysis(URLScan $scan): array
    {
        $emailMetadata = $scan->email_metadata ?? [];
        $emailAnalysis = $emailMetadata['email_analysis'] ?? [];

        return [
            'sender_email' => $scan->sender_email,
            'email_subject' => $scan->email_subject,
            'risk_score' => $emailAnalysis['risk_score'] ?? 0,
            'indicators' => $emailAnalysis['indicators'] ?? [],
            'severity' => $emailAnalysis['severity'] ?? 'low'
        ];
    }

    protected function getImageAnalysis(URLScan $scan): ?array
    {
        $emailMetadata = $scan->email_metadata ?? [];
        $imageAnalysis = $emailMetadata['image_analysis'] ?? null;

        if (!$imageAnalysis) {
            return null;
        }

        return [
            'has_image' => !empty($scan->image_url),
            'image_url' => $scan->image_url,
            'alt_text' => $scan->image_alt,
            'risk_score' => $imageAnalysis['risk_score'] ?? 0,
            'indicators' => $imageAnalysis['indicators'] ?? []
        ];
    }

    protected function getStatusMessage(string $status): string
    {
        return match($status) {
            'safe' => '✅ This link appears safe. No phishing indicators detected.',
            'suspicious' => '⚠️ This link shows suspicious characteristics. Exercise caution.',
            'dangerous' => '🚫 This link appears to be a phishing attempt. DO NOT proceed!',
            default => 'Unable to determine link safety.'
        };
    }
}