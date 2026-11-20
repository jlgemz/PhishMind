<?php

namespace App\Http\Controllers;

use App\Models\URLScan;
use App\Services\MLService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class URLInterceptorController extends Controller
{
    protected $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Handle URL input from user (auto-detects protocol)
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:2048'
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Please enter a valid URL');
        }

        $input = $request->input('url');
        
        // Auto-detect and normalize URL
        $normalizedUrl = $this->normalizeUrl($input);
        
        if (!$normalizedUrl) {
            return back()->with('error', 'Invalid URL format. Please enter a valid URL.');
        }

        // Check if already scanned
        $existingScan = URLScan::where('url', $normalizedUrl)
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($existingScan) {
            return $this->handleScanResult($existingScan, $normalizedUrl);
        }

        // Perform new scan
        $result = $this->performScan($normalizedUrl);
        
        return $this->handleScanResult($result, $normalizedUrl);
    }

    /**
     * Normalize URL - auto-add protocol if missing
     */
    protected function normalizeUrl(string $input): ?string
    {
        $input = trim($input);
        
        // Check if it's already a valid URL
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            return $input;
        }
        
        // Try adding https://
        $withHttps = 'https://' . $input;
        if (filter_var($withHttps, FILTER_VALIDATE_URL)) {
            return $withHttps;
        }
        
        // Try adding http://
        $withHttp = 'http://' . $input;
        if (filter_var($withHttp, FILTER_VALIDATE_URL)) {
            return $withHttp;
        }
        
        // Try adding www.
        if (!str_starts_with($input, 'www.')) {
            $withWww = 'https://www.' . $input;
            if (filter_var($withWww, FILTER_VALIDATE_URL)) {
                return $withWww;
            }
        }
        
        // Check if it's a domain without protocol
        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $input)) {
            return 'https://' . $input;
        }
        
        return null;
    }

    /**
     * Perform the actual scan
     */
    protected function performScan(string $url): URLScan
    {
        // Get ML analysis
        $mlResult = $this->mlService->analyze($url);
        
        $status = 'safe';
        $confidence = 0;
        $riskIndicators = [];
        
        // Extract risk indicators
        if (isset($mlResult['risk_indicators'])) {
            $riskIndicators = $mlResult['risk_indicators'];
        }
        
        // Calculate confidence and status
        if (isset($mlResult['prediction'])) {
            $confidence = $mlResult['prediction'] === 1 ? 
                $mlResult['confidence'] * 100 : 
                (1 - $mlResult['confidence']) * 100;
            
            if ($confidence > 70) {
                $status = 'dangerous';
            } elseif ($confidence > 40) {
                $status = 'suspicious';
            }
        }
        
        // Add auto-detected indicator
        if (empty($riskIndicators)) {
            $riskIndicators = ['No specific risk indicators detected'];
        }
        
        // Save to database
        $scan = URLScan::create([
            'url' => $url,
            'domain' => parse_url($url, PHP_URL_HOST) ?? $url,
            'status' => $status,
            'confidence_score' => $confidence,
            'scan_results' => $riskIndicators,
            'metadata' => [
                'ml_result' => $mlResult,
                'scanned_at' => now()->toDateTimeString(),
                'normalized_from' => $url
            ]
        ]);
        
        return $scan;
    }

    /**
     * Handle scan result - redirect or block
     */
    protected function handleScanResult($scan, string $url)
    {
        // If it's a model instance, use it directly
        if ($scan instanceof URLScan) {
            $status = $scan->status;
            $confidence = $scan->confidence_score;
        } else {
            // If it's an array (from existing scan)
            $status = $scan['status'] ?? 'safe';
            $confidence = $scan['confidence_score'] ?? 0;
        }
        
        // Log the action
        Log::info("URL Interceptor", [
            'url' => $url,
            'status' => $status,
            'confidence' => $confidence
        ]);
        
        // If dangerous or suspicious with high confidence, block
        if ($status === 'dangerous' || ($status === 'suspicious' && $confidence > 60)) {
            // Block and show warning
            return redirect()->route('blocked', [
                'url' => urlencode($url),
                'scan_id' => $scan instanceof URLScan ? $scan->id : null
            ]);
        }
        
        // If safe or low confidence suspicious, redirect
        if ($status === 'safe' || ($status === 'suspicious' && $confidence <= 60)) {
            // Check if we should show a warning for suspicious
            if ($status === 'suspicious') {
                session()->flash('warning', 'This URL showed some suspicious characteristics. Proceed with caution.');
            }
            
            // Redirect to the URL
            return redirect()->away($url);
        }
        
        // Default: redirect anyway but with warning
        session()->flash('info', 'Proceeding to URL. Please stay vigilant.');
        return redirect()->away($url);
    }

    /**
     * Handle direct URL access (for testing)
     */
    public function go(Request $request)
    {
        $url = $request->query('url');
        
        if (!$url) {
            return redirect()->route('home');
        }
        
        // Normalize
        $normalized = $this->normalizeUrl($url);
        
        if (!$normalized) {
            return redirect()->route('home')->with('error', 'Invalid URL');
        }
        
        // Check if blocked
        $scan = URLScan::where('url', $normalized)
            ->where('status', 'dangerous')
            ->first();
            
        if ($scan) {
            return redirect()->route('blocked', ['url' => urlencode($normalized)]);
        }
        
        // Proceed
        return redirect()->away($normalized);
    }
}
