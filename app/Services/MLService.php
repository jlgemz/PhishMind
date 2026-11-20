<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MLService
{
    protected $mlServiceUrl;
    protected $timeout;
    protected $detector;
    protected $threatIntel;

    public function __construct()
    {
        $this->mlServiceUrl = env('ML_SERVICE_URL', 'http://localhost:8001');
        $this->timeout = 10;
        $this->detector = app(PhishingDetectorService::class);
        $this->threatIntel = app(ThreatIntelligenceService::class);
    }

    public function analyze(string $url): array
    {
        try {
            $cacheKey = 'ml_' . md5($url);
            
            // Check cache
            if (Cache::has($cacheKey)) {
                Log::info("ML result from cache for: " . $url);
                $cached = Cache::get($cacheKey);
                // Add threat intelligence to cached result
                $cached['threat_intelligence'] = $this->threatIntel->comprehensiveCheck($url);
                return $cached;
            }

            Log::info("Calling ML service for: " . $url);
            
            // Get ML prediction
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->mlServiceUrl}/predict", [
                    'url' => $url
                ]);

            $result = [];
            
            if ($response->successful()) {
                $result = $response->json();
                Log::info("ML service response received", ['url' => $url]);
            } else {
                Log::warning("ML Service returned error", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $result = $this->getFallbackAnalysis($url);
            }

            // Add threat intelligence
            $result['threat_intelligence'] = $this->threatIntel->comprehensiveCheck($url);
            
            // Combine risk indicators
            $mlIndicators = $result['risk_indicators'] ?? [];
            $tiIndicators = $this->getThreatIntelIndicators($result['threat_intelligence']);
            $result['risk_indicators'] = array_merge($mlIndicators, $tiIndicators);
            
            // Recalculate confidence based on threat intelligence
            if (isset($result['threat_intelligence']['threat_score'])) {
                $tiScore = $result['threat_intelligence']['threat_score'];
                $mlConfidence = $result['confidence'] ?? 0.5;
                // Weighted average: 60% ML, 40% Threat Intel
                $result['confidence'] = ($mlConfidence * 0.6) + (($tiScore / 100) * 0.4);
                $result['confidence'] = min(1, max(0, $result['confidence']));
            }
            
            // Cache for 1 hour
            Cache::put($cacheKey, $result, 3600);
            return $result;

        } catch (\Exception $e) {
            Log::error("ML Service error: " . $e->getMessage());
            return $this->getFallbackAnalysis($url);
        }
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->mlServiceUrl}/health");
            
            $healthy = $response->successful();
            Log::info("ML Health check: " . ($healthy ? "Online" : "Offline"));
            return $healthy;
        } catch (\Exception $e) {
            Log::error("ML Health check failed: " . $e->getMessage());
            return false;
        }
    }

    protected function getFallbackAnalysis(string $url): array
    {
        Log::info("Using fallback analysis for: " . $url);
        
        $analysis = $this->detector->analyzeURL($url);
        
        return [
            'url' => $url,
            'prediction' => $analysis['status'] === 'dangerous' ? 1 : 0,
            'confidence' => $analysis['confidence_score'] / 100,
            'probability_phishing' => $analysis['confidence_score'] / 100,
            'probability_legitimate' => 1 - ($analysis['confidence_score'] / 100),
            'features' => $analysis['metadata'] ?? [],
            'risk_level' => strtoupper($analysis['status']),
            'risk_indicators' => $analysis['indicators'] ?? ['No risk indicators detected'],
            'fallback' => true
        ];
    }

    protected function getThreatIntelIndicators(array $threatIntel): array
    {
        $indicators = [];
        
        if (isset($threatIntel['summary'])) {
            $indicators[] = "Threat Intelligence: " . $threatIntel['summary'];
        }
        
        if (isset($threatIntel['virustotal']['detected']) && $threatIntel['virustotal']['detected']) {
            $indicators[] = "⚠️ Detected by " . $threatIntel['virustotal']['malicious'] . 
                           " antivirus engines on VirusTotal";
        }
        
        if (isset($threatIntel['google_safe_browsing']['safe']) && 
            !$threatIntel['google_safe_browsing']['safe']) {
            $types = array_column($threatIntel['google_safe_browsing']['threats'] ?? [], 'type');
            $indicators[] = "🚫 Flagged by Google Safe Browsing: " . implode(', ', $types);
        }
        
        if (isset($threatIntel['abuseipdb']['is_abusive']) && 
            $threatIntel['abuseipdb']['is_abusive']) {
            $indicators[] = "⚠️ IP has abuse reports (score: " . 
                           $threatIntel['abuseipdb']['abuse_score'] . "%)";
        }
        
        return $indicators;
    }
}
