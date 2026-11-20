<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PhishingDetectorService
{
    protected $rules = [];
    
    public function __construct()
    {
        $this->loadRules();
    }

    /**
     * Load detection rules
     */
    protected function loadRules(): void
    {
        $this->rules = [
            'suspicious_keywords' => [
                'login', 'verify', 'update', 'confirm', 'secure', 
                'banking', 'account', 'password', 'credential',
                'signin', 'authenticate', 'validate', 'recover',
                'reset', 'activate', 'authorize', 'verification',
                'security', 'alert', 'warning', 'suspicious',
                'paypal', 'amazon', 'google', 'facebook', 'microsoft',
                'apple', 'bank', 'secure', 'online', 'access'
            ],
            'suspicious_tlds' => [
                '.tk', '.ml', '.ga', '.cf', '.top', '.xyz',
                '.work', '.date', '.download', '.bid', '.win',
                '.club', '.online', '.site', '.space', '.tech',
                '.store', '.shop', '.live', '.click', '.stream'
            ],
            'suspicious_domains' => [
                'google.com', 'facebook.com', 'amazon.com',
                'microsoft.com', 'apple.com', 'paypal.com',
                'netflix.com', 'spotify.com', 'twitter.com'
            ]
        ];
    }

    /**
     * Analyze URL for phishing indicators
     */
    public function analyzeURL(string $url): array
    {
        $urlInfo = $this->parseURL($url);
        $indicators = [];
        $score = 0;

        // 1. Check suspicious keywords in URL
        if ($this->checkSuspiciousKeywords($url)) {
            $indicators[] = 'Suspicious keywords detected in URL';
            $score += 15;
        }

        // 2. Check suspicious TLDs
        if ($this->checkSuspiciousTLD($url)) {
            $indicators[] = 'Suspicious TLD detected';
            $score += 10;
        }

        // 3. Check URL length
        if (strlen($url) > 100) {
            $indicators[] = 'URL is excessively long (potential obfuscation)';
            $score += 10;
        }

        // 4. Check for IP address in URL
        if ($this->hasIPAddress($url)) {
            $indicators[] = 'URL uses IP address instead of domain name';
            $score += 25;
        }

        // 5. Check for multiple subdomains
        if ($this->countSubdomains($url) > 3) {
            $indicators[] = 'Multiple subdomains detected (possible redirection)';
            $score += 10;
        }

        // 6. Check for suspicious characters
        if ($this->hasSuspiciousCharacters($url)) {
            $indicators[] = 'Suspicious characters in URL';
            $score += 10;
        }

        // 7. Check for HTTPS
        if (!$this->hasValidSSL($url)) {
            $indicators[] = 'No valid SSL certificate (HTTP instead of HTTPS)';
            $score += 15;
        }

        // 8. Check for brand spoofing
        if ($this->checkBrandSpoofing($url)) {
            $indicators[] = 'Potential brand spoofing detected';
            $score += 20;
        }

        // 9. Check for URL shorteners (common in phishing)
        if ($this->isURLShortener($url)) {
            $indicators[] = 'URL shortener detected (used to hide destination)';
            $score += 10;
        }

        // Determine final status
        $status = $this->determineStatus($score);
        $confidence = $this->calculateConfidence($score, count($indicators));

        return [
            'url' => $url,
            'domain' => $urlInfo['domain'] ?? parse_url($url, PHP_URL_HOST) ?? $url,
            'status' => $status,
            'confidence_score' => $confidence,
            'indicators' => $indicators,
            'score' => $score,
            'scan_date' => now()->toDateTimeString(),
            'metadata' => [
                'has_ssl' => $this->hasValidSSL($url),
                'url_length' => strlen($url),
                'subdomain_count' => $this->countSubdomains($url),
                'has_ip' => $this->hasIPAddress($url)
            ]
        ];
    }

    /**
     * Parse URL into components
     */
    protected function parseURL(string $url): array
    {
        $parts = parse_url($url);
        $domain = $parts['host'] ?? $parts['path'] ?? '';
        
        // Clean domain
        $domain = str_replace('www.', '', $domain);
        
        return [
            'scheme' => $parts['scheme'] ?? 'http',
            'host' => $parts['host'] ?? '',
            'domain' => $domain,
            'path' => $parts['path'] ?? '',
            'query' => $parts['query'] ?? ''
        ];
    }

    /**
     * Check for suspicious keywords
     */
    protected function checkSuspiciousKeywords(string $url): bool
    {
        $urlLower = strtolower($url);
        foreach ($this->rules['suspicious_keywords'] as $keyword) {
            if (strpos($urlLower, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check for suspicious TLD
     */
    protected function checkSuspiciousTLD(string $url): bool
    {
        $urlLower = strtolower($url);
        foreach ($this->rules['suspicious_tlds'] as $tld) {
            if (strpos($urlLower, $tld) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if URL contains IP address
     */
    protected function hasIPAddress(string $url): bool
    {
        return preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $url) === 1;
    }

    /**
     * Count number of subdomains
     */
    protected function countSubdomains(string $url): int
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return 0;
        
        $parts = explode('.', $host);
        // Remove TLD and second-level domain
        return max(0, count($parts) - 2);
    }

    /**
     * Check for suspicious characters
     */
    protected function hasSuspiciousCharacters(string $url): bool
    {
        $suspiciousChars = ['@', '//', '\\\\', '%00', '%20', '..', '\\', ';', '(', ')'];
        foreach ($suspiciousChars as $char) {
            if (strpos($url, $char) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check SSL certificate
     */
    protected function hasValidSSL(string $url): bool
    {
        return strpos($url, 'https://') === 0;
    }

    /**
     * Check for brand spoofing
     */
    protected function checkBrandSpoofing(string $url): bool
    {
        $popularBrands = ['google', 'facebook', 'amazon', 'microsoft', 'apple', 
                         'paypal', 'bank', 'secure', 'account', 'login'];
        $urlLower = strtolower($url);
        
        foreach ($popularBrands as $brand) {
            if (strpos($urlLower, $brand) !== false) {
                // Check if brand is in domain or path
                $parsed = parse_url($url);
                if (isset($parsed['host']) && strpos(strtolower($parsed['host']), $brand) !== false) {
                    return true;
                }
                if (isset($parsed['path']) && strpos(strtolower($parsed['path']), $brand) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if URL uses a shortener
     */
    protected function isURLShortener(string $url): bool
    {
        $shorteners = [
            'bit.ly', 'tinyurl.com', 'goo.gl', 'short.url', 'ow.ly',
            'is.gd', 'buff.ly', 'adf.ly', 'shorte.st', 'bc.vc',
            't.co', 'lnkd.in', 'db.tt', 'qr.ae', 'cur.lv'
        ];
        
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return false;
        
        foreach ($shorteners as $shortener) {
            if (strpos($host, $shortener) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine status based on score
     */
    protected function determineStatus(float $score): string
    {
        if ($score >= 70) {
            return 'dangerous';
        } elseif ($score >= 40) {
            return 'suspicious';
        }
        return 'safe';
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(float $score, int $indicatorCount): float
    {
        // Base confidence on number of indicators and score
        $baseConfidence = min(100, $score + ($indicatorCount * 5));
        // Normalize to 0-100 range
        return min(100, max(0, $baseConfidence));
    }

    /**
     * Get detailed scan results
     */
    public function getDetailedResults(array $analysis): array
    {
        return [
            'url' => $analysis['url'],
            'domain' => $analysis['domain'],
            'status' => $analysis['status'],
            'confidence' => $analysis['confidence_score'],
            'risk_indicators' => $analysis['indicators'],
            'metadata' => $analysis['metadata'],
            'recommendation' => $this->getRecommendation($analysis['status'])
        ];
    }

    /**
     * Get recommendation based on status
     */
    protected function getRecommendation(string $status): string
    {
        return match($status) {
            'safe' => 'This URL appears to be safe. You can proceed with caution.',
            'suspicious' => 'This URL shows suspicious characteristics. Exercise caution and verify before proceeding.',
            'dangerous' => 'This URL appears to be dangerous. Do NOT proceed and report it immediately.',
            default => 'Unable to determine. Please proceed with caution.'
        };
    }
}
