<?php

namespace App\Http\Controllers;

use App\Models\URLScan;
use App\Services\MLService;
use App\Services\EmailPhishingDetector;
use App\Services\ImagePhishingDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PhishGuardController extends Controller
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

    public function index()
    {
        // Check if email columns exist
        $hasEmailColumns = Schema::hasColumn('url_scans', 'detection_source');
        $hasImageColumns = Schema::hasColumn('url_scans', 'image_url');

        $stats = [
            'total_scans' => URLScan::count(),
            'safe_urls' => URLScan::where('status', 'safe')->count(),
            'suspicious_urls' => URLScan::where('status', 'suspicious')->count(),
            'dangerous_urls' => URLScan::where('status', 'dangerous')->count(),
            'ml_available' => $this->mlService->isHealthy(),
        ];

        // Only add email stats if columns exist
        if ($hasEmailColumns) {
            $stats['email_scans'] = URLScan::where('detection_source', 'email')->count();
            $stats['web_scans'] = URLScan::where('detection_source', 'web')->count();
            $stats['email_phishing'] = URLScan::where('detection_source', 'email')
                ->whereIn('status', ['dangerous', 'suspicious'])
                ->count();
            
            if ($hasImageColumns) {
                $stats['email_images'] = URLScan::where('detection_source', 'email')
                    ->whereNotNull('image_url')
                    ->count();
            } else {
                $stats['email_images'] = 0;
            }
        } else {
            $stats['email_scans'] = 0;
            $stats['web_scans'] = $stats['total_scans'];
            $stats['email_phishing'] = 0;
            $stats['email_images'] = 0;
        }

        $recentScans = URLScan::latest()->take(10)->get();

        return view('phishguard.index', compact('stats', 'recentScans'));
    }

    /**
     * Scan URL with full context (web + email + image)
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:2048',
            'source' => 'nullable|in:web,email',
            'sender_email' => 'nullable|email|max:255',
            'email_subject' => 'nullable|string|max:500',
            'image' => 'nullable|array',
            'image.img_src' => 'nullable|url',
            'image.alt_text' => 'nullable|string|max:500',
            'image.img_title' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $url = $request->input('url');
            $source = $request->input('source', 'web');
            $senderEmail = $request->input('sender_email');
            $emailSubject = $request->input('email_subject');
            $imageData = $request->input('image');

            // Check cache
            $existingScan = URLScan::where('url', $url)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if ($existingScan) {
                return response()->json([
                    'status' => 'success',
                    'data' => $this->formatScanResult($existingScan),
                    'cached' => true
                ]);
            }

            // Perform comprehensive scan
            $scan = $this->performComprehensiveScan($url, $source, $senderEmail, $emailSubject, $imageData);

            return response()->json([
                'status' => 'success',
                'data' => $this->formatScanResult($scan),
                'cached' => false,
                'email_analysis' => $source === 'email' ? $this->getEmailAnalysis($scan) : null,
                'image_analysis' => $imageData ? $this->getImageAnalysis($scan) : null
            ]);

        } catch (\Exception $e) {
            Log::error('Scan error: ' . $e->getMessage(), [
                'url' => $url ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to scan URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform comprehensive scan
     */
    protected function performComprehensiveScan(
        string $url,
        string $source,
        ?string $senderEmail,
        ?string $emailSubject,
        ?array $imageData
    ): URLScan {
        // 1. ML analysis
        $mlResult = $this->mlService->analyze($url);

        // 2. Email analysis
        $emailAnalysis = null;
        if ($source === 'email' && ($senderEmail || $emailSubject)) {
            $emailAnalysis = $this->emailDetector->analyzeEmail($url, $senderEmail, $emailSubject, $imageData);
        }

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
        if ($emailAnalysis && isset($emailAnalysis['indicators'])) {
            $indicators = array_merge($indicators, $emailAnalysis['indicators']);
        }
        if ($imageAnalysis && isset($imageAnalysis['indicators'])) {
            $indicators = array_merge($indicators, $imageAnalysis['indicators']);
        }
        $indicators = array_unique(array_filter($indicators));

        if (empty($indicators)) {
            $indicators = ['No risk indicators detected'];
        }

        // 7. Extract sender domain
        $senderDomain = null;
        if ($senderEmail) {
            $senderDomain = substr(strrchr($senderEmail, "@"), 1);
        }

        // 8. Prepare data for saving
        $data = [
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST) ?? $url,
            'status' => $status,
            'confidence_score' => $combinedScore,
            'scan_results' => $indicators,
            'metadata' => [
                'ml_result' => $mlResult,
                'scanned_at' => now()->toDateTimeString()
            ]
        ];

        // Only add email fields if they exist in the database
        if (Schema::hasColumn('url_scans', 'detection_source')) {
            $data['detection_source'] = $source;
        }

        if (Schema::hasColumn('url_scans', 'sender_email')) {
            $data['sender_email'] = $senderEmail;
        }

        if (Schema::hasColumn('url_scans', 'email_subject')) {
            $data['email_subject'] = $emailSubject;
        }

        if (Schema::hasColumn('url_scans', 'email_domain')) {
            $data['email_domain'] = $senderDomain;
        }

        if (Schema::hasColumn('url_scans', 'image_url')) {
            $data['image_url'] = $imageData['img_src'] ?? null;
        }

        if (Schema::hasColumn('url_scans', 'image_alt')) {
            $data['image_alt'] = $imageData['alt_text'] ?? null;
        }

        if (Schema::hasColumn('url_scans', 'email_metadata')) {
            $data['email_metadata'] = [
                'email_analysis' => $emailAnalysis,
                'image_analysis' => $imageAnalysis,
                'image_data' => $imageData
            ];
        }

        // 9. Save to database
        $scan = URLScan::create($data);

        return $scan;
    }

    /**
     * Format scan result
     */
    protected function formatScanResult(URLScan $scan): array
    {
        $riskIndicators = $scan->scan_results;
        if (is_string($riskIndicators)) {
            $riskIndicators = json_decode($riskIndicators, true) ?? ['No risk indicators detected'];
        }
        if (!is_array($riskIndicators) || empty($riskIndicators)) {
            $riskIndicators = ['No risk indicators detected'];
        }

        $result = [
            'id' => $scan->id,
            'url' => $scan->url,
            'domain' => $scan->domain,
            'status' => $scan->status,
            'confidence_score' => $scan->confidence_score,
            'risk_indicators' => $riskIndicators,
            'scanned_at' => $scan->created_at->format('Y-m-d H:i:s'),
            'status_color' => $this->getStatusColor($scan->status),
            'icon' => $this->getStatusIcon($scan->status),
        ];

        // Only add email fields if they exist
        if (Schema::hasColumn('url_scans', 'detection_source')) {
            $result['source'] = $scan->detection_source ?? 'web';
        }

        if (Schema::hasColumn('url_scans', 'sender_email')) {
            $result['sender_email'] = $scan->sender_email;
        }

        if (Schema::hasColumn('url_scans', 'email_subject')) {
            $result['email_subject'] = $scan->email_subject;
        }

        if (Schema::hasColumn('url_scans', 'image_url')) {
            $result['has_image'] = !empty($scan->image_url);
        }

        return $result;
    }

    /**
     * Get email analysis
     */
    protected function getEmailAnalysis(URLScan $scan): ?array
    {
        if (!Schema::hasColumn('url_scans', 'email_metadata')) {
            return null;
        }

        $emailMetadata = $scan->email_metadata ?? [];
        $emailAnalysis = $emailMetadata['email_analysis'] ?? null;

        if (!$emailAnalysis) {
            return null;
        }

        return [
            'sender_email' => $scan->sender_email ?? null,
            'email_subject' => $scan->email_subject ?? null,
            'email_domain' => $scan->email_domain ?? null,
            'risk_score' => $emailAnalysis['risk_score'] ?? 0,
            'indicators' => $emailAnalysis['indicators'] ?? [],
            'severity' => $emailAnalysis['severity'] ?? 'low'
        ];
    }

    /**
     * Get image analysis
     */
    protected function getImageAnalysis(URLScan $scan): ?array
    {
        if (!Schema::hasColumn('url_scans', 'email_metadata')) {
            return null;
        }

        $emailMetadata = $scan->email_metadata ?? [];
        $imageAnalysis = $emailMetadata['image_analysis'] ?? null;
        $imageData = $emailMetadata['image_data'] ?? null;

        if (!$imageAnalysis && !$imageData) {
            return null;
        }

        return [
            'has_image' => !empty($imageData),
            'image_url' => $scan->image_url ?? null,
            'alt_text' => $scan->image_alt ?? null,
            'risk_score' => $imageAnalysis['risk_score'] ?? 0,
            'indicators' => $imageAnalysis['indicators'] ?? [],
            'image_size' => $imageAnalysis['image_size'] ?? null
        ];
    }

    protected function getStatusColor(string $status): string
    {
        return match($status) {
            'safe' => 'success',
            'suspicious' => 'warning',
            'dangerous' => 'danger',
            default => 'secondary'
        };
    }

    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'safe' => 'shield-check',
            'suspicious' => 'exclamation-triangle',
            'dangerous' => 'x-circle',
            default => 'question-circle'
        };
    }
}