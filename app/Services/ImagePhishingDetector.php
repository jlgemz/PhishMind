<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImagePhishingDetector
{
    /**
     * Detect phishing through image analysis
     */
    public function analyzeImage(string $imageUrl, string $targetUrl): array
    {
        $indicators = [];
        $riskScore = 0;

        // 1. Check if image URL is suspicious
        $imageAnalysis = $this->analyzeImageUrl($imageUrl);
        $indicators = array_merge($indicators, $imageAnalysis['indicators']);
        $riskScore += $imageAnalysis['riskScore'];

        // 2. Check for brand logos in image
        $brandAnalysis = $this->detectBrandLogo($imageUrl);
        $indicators = array_merge($indicators, $brandAnalysis['indicators']);
        $riskScore += $brandAnalysis['riskScore'];

        // 3. Check if image is from suspicious domain
        $domainAnalysis = $this->checkImageDomain($imageUrl, $targetUrl);
        $indicators = array_merge($indicators, $domainAnalysis['indicators']);
        $riskScore += $domainAnalysis['riskScore'];

        // 4. Check for common phishing image patterns
        $patternAnalysis = $this->checkImagePatterns($imageUrl);
        $indicators = array_merge($indicators, $patternAnalysis['indicators']);
        $riskScore += $patternAnalysis['riskScore'];

        // 5. Try to analyze image content (optional)
        $contentAnalysis = $this->analyzeImageContent($imageUrl);
        if (isset($contentAnalysis['warning'])) {
            $indicators[] = $contentAnalysis['warning'];
            $riskScore += 10;
        }

        return [
            'risk_score' => min(100, $riskScore),
            'indicators' => array_unique($indicators),
            'is_phishing' => $riskScore >= 50,
            'image_size' => $contentAnalysis['size'] ?? null,
            'image_type' => $contentAnalysis['type'] ?? null
        ];
    }

    /**
     * Analyze image URL for suspicious patterns
     */
    protected function analyzeImageUrl(string $imageUrl): array
    {
        $indicators = [];
        $riskScore = 0;

        // Suspicious image domains
        $suspiciousImageDomains = [
            'imgur.com', 'ibb.co', 'postimg.cc', 'imageshack.com',
            'tinypic.com', 'photobucket.com', 'imagehost.com',
            'upload.cc', 'imgbb.com', 'imagevenue.com',
            'pixhost.to', 'imagebam.com', 'pixxxels.org'
        ];

        $imageDomain = parse_url($imageUrl, PHP_URL_HOST);
        if ($imageDomain) {
            foreach ($suspiciousImageDomains as $domain) {
                if (strpos($imageDomain, $domain) !== false) {
                    $indicators[] = "Image hosted on suspicious domain: '{$domain}'";
                    $riskScore += 15;
                    break;
                }
            }
        }

        // Check for random-looking image filenames
        if (preg_match('/[0-9a-f]{16,}/i', basename($imageUrl))) {
            $indicators[] = "Image has random-looking filename (potential obfuscation)";
            $riskScore += 10;
        }

        // Check for timestamp in image URL (tracking)
        if (preg_match('/[0-9]{10,}/', $imageUrl)) {
            $indicators[] = "Image URL contains timestamp (possible tracking)";
            $riskScore += 5;
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Detect brand logos in images
     */
    protected function detectBrandLogo(string $imageUrl): array
    {
        $brandLogos = [
            'paypal' => ['paypal-logo', 'paypal_logo', 'pp-logo', 'paypal'],
            'google' => ['google-logo', 'google_logo', 'g-logo', 'google'],
            'facebook' => ['facebook-logo', 'fb-logo', 'f_logo', 'facebook'],
            'amazon' => ['amazon-logo', 'amazon_logo', 'amazon'],
            'microsoft' => ['microsoft-logo', 'ms-logo', 'microsoft'],
            'apple' => ['apple-logo', 'apple_logo', 'apple'],
            'netflix' => ['netflix-logo', 'netflix_logo', 'netflix'],
            'spotify' => ['spotify-logo', 'spotify'],
            'ebay' => ['ebay-logo', 'ebay_logo', 'ebay'],
            'chase' => ['chase-logo', 'chase_logo', 'chase'],
            'bank' => ['bank-logo', 'bank_logo'],
            'mastercard' => ['mastercard-logo', 'mastercard'],
            'visa' => ['visa-logo', 'visa_logo', 'visa']
        ];

        $imageLower = strtolower($imageUrl);
        $indicators = [];
        $riskScore = 0;

        foreach ($brandLogos as $brand => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($imageLower, $pattern) !== false) {
                    $indicators[] = "Image contains '{$brand}' logo branding";
                    $riskScore += 10;
                    break 2;
                }
            }
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Check if image domain matches target URL domain
     */
    protected function checkImageDomain(string $imageUrl, string $targetUrl): array
    {
        $imageDomain = parse_url($imageUrl, PHP_URL_HOST);
        $targetDomain = parse_url($targetUrl, PHP_URL_HOST);

        if (!$imageDomain || !$targetDomain) {
            return ['riskScore' => 0, 'indicators' => []];
        }

        $indicators = [];
        $riskScore = 0;

        $imageDomain = str_replace('www.', '', $imageDomain);
        $targetDomain = str_replace('www.', '', $targetDomain);

        if ($imageDomain !== $targetDomain) {
            $indicators[] = "Image domain '{$imageDomain}' doesn't match link domain '{$targetDomain}'";
            $riskScore += 20;
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Check for common phishing image patterns
     */
    protected function checkImagePatterns(string $imageUrl): array
    {
        $indicators = [];
        $riskScore = 0;
        $imageLower = strtolower($imageUrl);

        // Common phishing image filenames
        $phishingImageNames = [
            'verify', 'confirm', 'secure', 'login', 'account',
            'activate', 'validate', 'authenticate', 'password',
            'reset', 'recover', 'update', 'payment', 'invoice',
            'button', 'click', 'sign', 'continue', 'proceed'
        ];

        foreach ($phishingImageNames as $name) {
            if (strpos($imageLower, $name) !== false) {
                $indicators[] = "Image filename contains phishing keyword: '{$name}'";
                $riskScore += 15;
                break;
            }
        }

        // Check for suspicious image dimensions
        if (preg_match('/[_-]?(?:[0-9]{2,3}x[0-9]{2,3})/', $imageLower)) {
            $indicators[] = "Image URL contains dimension parameters (common in tracking)";
            $riskScore += 5;
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Download and analyze image content
     */
    public function analyzeImageContent(string $imageUrl): array
    {
        try {
            $response = Http::timeout(10)->get($imageUrl);

            if (!$response->successful()) {
                return ['error' => 'Could not download image'];
            }

            $imageData = $response->body();
            $imageSize = strlen($imageData);

            // Check if image size is suspicious
            if ($imageSize < 1000) {
                return [
                    'warning' => 'Image is very small (possible tracking pixel)',
                    'size' => $imageSize
                ];
            }

            // Check if image is too large
            if ($imageSize > 5000000) {
                return [
                    'warning' => 'Image is very large (possible payload)',
                    'size' => $imageSize
                ];
            }

            return [
                'size' => $imageSize,
                'type' => $response->header('Content-Type')
            ];

        } catch (\Exception $e) {
            Log::error('Image download failed: ' . $e->getMessage());
            return ['error' => 'Image analysis failed'];
        }
    }
}