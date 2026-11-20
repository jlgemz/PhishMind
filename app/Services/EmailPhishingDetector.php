<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmailPhishingDetector
{
    // Urgency keywords in email subjects
    protected $urgencyKeywords = [
        'urgent', 'immediate', 'action required', 'verify your', 'confirm your',
        'suspended', 'locked', 'security alert', 'unauthorized access',
        'account compromised', 'update your', 'reactivate', 'pending verification',
        'important notice', 'final warning', 'limited time', 'expires today',
        'payment failed', 'billing issue', 'invoice attached', 'refund request',
        'unusual activity', 'sign in', 'reset password', 'account verification'
    ];

    // Suspicious sender patterns
    protected $suspiciousSenderPatterns = [
        'security@', 'verify@', 'support@', 'admin@', 'service@',
        'info@', 'team@', 'noreply@', 'help@', 'care@',
        'accounts@', 'billing@', 'payment@', 'update@', 'alert@'
    ];

    // High-risk domains
    protected $suspiciousDomains = [
        'mail.com', 'yandex.com', 'protonmail.com', 'gmx.com',
        'outlook.com', 'icloud.com', 'aol.com', 'yahoo.com',
        'gmail.com', 'hotmail.com', 'live.com', 'msn.com'
    ];

    /**
     * Main analysis method - analyzes email for phishing
     */
    public function analyzeEmail(string $url, ?string $sender, ?string $subject, ?array $imageData = null): array
    {
        $indicators = [];
        $riskScore = 0;

        // 1. Analyze sender
        if ($sender) {
            $senderAnalysis = $this->analyzeSender($sender);
            $indicators = array_merge($indicators, $senderAnalysis['indicators']);
            $riskScore += $senderAnalysis['riskScore'];
        }

        // 2. Analyze subject
        if ($subject) {
            $subjectAnalysis = $this->analyzeSubject($subject);
            $indicators = array_merge($indicators, $subjectAnalysis['indicators']);
            $riskScore += $subjectAnalysis['riskScore'];
        }

        // 3. Check domain mismatch
        if ($sender && $url) {
            $domainAnalysis = $this->checkDomainMismatch($url, $sender);
            $indicators = array_merge($indicators, $domainAnalysis['indicators']);
            $riskScore += $domainAnalysis['riskScore'];
        }

        // 4. Check URL shorteners
        if ($url) {
            $shortenerAnalysis = $this->checkUrlShortener($url);
            $indicators = array_merge($indicators, $shortenerAnalysis['indicators']);
            $riskScore += $shortenerAnalysis['riskScore'];
        }

        // 5. Check brand impersonation
        if ($url) {
            $brandAnalysis = $this->checkBrandImpersonation($url);
            $indicators = array_merge($indicators, $brandAnalysis['indicators']);
            $riskScore += $brandAnalysis['riskScore'];
        }

        // 6. Analyze image (if present)
        if ($imageData) {
            $imageAnalysis = $this->analyzeImage($url, $imageData);
            $indicators = array_merge($indicators, $imageAnalysis['indicators']);
            $riskScore += $imageAnalysis['riskScore'];
        }

        // 7. Check for suspicious links count
        if ($url) {
            $linkAnalysis = $this->analyzeLinkStructure($url);
            $indicators = array_merge($indicators, $linkAnalysis['indicators']);
            $riskScore += $linkAnalysis['riskScore'];
        }

        return [
            'risk_score' => min(100, $riskScore),
            'indicators' => array_unique(array_filter($indicators)),
            'is_phishing' => $riskScore >= 50,
            'severity' => $this->getSeverity($riskScore)
        ];
    }

    /**
     * Analyze sender email
     */
    protected function analyzeSender(string $sender): array
    {
        $indicators = [];
        $riskScore = 0;
        $senderLower = strtolower($sender);

        // Check for suspicious sender patterns
        foreach ($this->suspiciousSenderPatterns as $pattern) {
            if (strpos($senderLower, $pattern) !== false) {
                $indicators[] = "Suspicious sender pattern: '{$pattern}' in email";
                $riskScore += 15;
                break;
            }
        }

        // Check for suspicious domains
        foreach ($this->suspiciousDomains as $domain) {
            if (strpos($senderLower, $domain) !== false) {
                $indicators[] = "Sender uses free email domain: '{$domain}'";
                $riskScore += 10;
                break;
            }
        }

        // Check for display name spoofing (e.g., "PayPal <hacker@gmail.com>")
        if (preg_match('/^[^<]*[A-Z][a-z]+/', $sender) && preg_match('/<.*@gmail\.com>/', $sender)) {
            $indicators[] = "Possible display name spoofing detected";
            $riskScore += 20;
        }

        // Check for number substitution (e.g., Paypa1 instead of PayPal)
        if (preg_match('/[0-9]/', $sender)) {
            $indicators[] = "Sender email contains numbers (possible impersonation)";
            $riskScore += 10;
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Analyze email subject
     */
    protected function analyzeSubject(string $subject): array
    {
        $indicators = [];
        $riskScore = 0;
        $subjectLower = strtolower($subject);

        // Check for urgency keywords
        foreach ($this->urgencyKeywords as $keyword) {
            if (strpos($subjectLower, $keyword) !== false) {
                $indicators[] = "Urgency keyword in subject: '{$keyword}'";
                $riskScore += 10;
                if (in_array($keyword, ['urgent', 'immediate', 'final warning', 'suspended'])) {
                    $riskScore += 5;
                }
                break;
            }
        }

        // Check for ALL CAPS
        if (strtoupper($subject) === $subject && strlen($subject) > 10) {
            $indicators[] = "Subject is in ALL CAPS (common in phishing)";
            $riskScore += 10;
        }

        // Check for multiple exclamation marks
        if (substr_count($subject, '!') >= 2) {
            $indicators[] = "Subject contains multiple exclamation marks (urgency tactic)";
            $riskScore += 5;
        }

        // Check for "RE:" or "FW:" patterns (replies/forwards)
        if (preg_match('/^(RE:|FW:|FWD:)/i', $subject)) {
            $indicators[] = "Subject uses reply/forward prefix (may be spoofed)";
            $riskScore += 5;
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Check domain mismatch between sender and URL
     */
    protected function checkDomainMismatch(string $url, string $sender): array
    {
        $indicators = [];
        $riskScore = 0;

        $urlDomain = parse_url($url, PHP_URL_HOST);
        if (!$urlDomain) {
            return ['riskScore' => 0, 'indicators' => []];
        }

        // Extract sender domain from email
        preg_match('/@([^>]+)/', $sender, $matches);
        $senderDomain = $matches[1] ?? '';

        if ($senderDomain && $urlDomain) {
            $senderDomain = str_replace('www.', '', $senderDomain);
            $urlDomain = str_replace('www.', '', $urlDomain);

            if ($senderDomain !== $urlDomain) {
                $indicators[] = "Domain mismatch: sender '{$senderDomain}' vs URL '{$urlDomain}'";
                $riskScore += 25;

                // Check if URL domain is a subdomain of sender domain
                if (strpos($urlDomain, $senderDomain) !== false) {
                    $riskScore += 10;
                    $indicators[] = "URL is a subdomain of sender domain (possible spoofing)";
                }
            }
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Check for URL shorteners
     */
    protected function checkUrlShortener(string $url): array
    {
        $shorteners = [
            'bit.ly', 'tinyurl.com', 'goo.gl', 'short.url', 'ow.ly',
            'is.gd', 'buff.ly', 'adf.ly', 'shorte.st', 'bc.vc',
            't.co', 'lnkd.in', 'db.tt', 'qr.ae', 'cur.lv',
            'tiny.cc', 'cli.gs', 'short.ie', 'x.co', 'tinyurl',
            'rb.gy', 'v.gd', 'cutt.ly', 'rebrand.ly', 'shortlink'
        ];

        $urlLower = strtolower($url);
        $indicators = [];
        $riskScore = 0;

        foreach ($shorteners as $shortener) {
            if (strpos($urlLower, $shortener) !== false) {
                $indicators[] = "URL shortener detected: '{$shortener}' (can hide destination)";
                $riskScore += 15;
                break;
            }
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Check for brand impersonation
     */
    protected function checkBrandImpersonation(string $url): array
    {
        $brands = [
            'paypal' => ['paypal', 'pay-pal', 'paypai', 'paypall'],
            'google' => ['google', 'g00gle', 'googie', 'goggle'],
            'facebook' => ['facebook', 'faceb00k', 'face-book', 'facebok'],
            'amazon' => ['amazon', 'amaz0n', 'amzn', 'amazone'],
            'microsoft' => ['microsoft', 'micr0soft', 'micro-soft'],
            'apple' => ['apple', 'app1e', 'appleid', 'appie'],
            'paypal' => ['paypal', 'pay-pal', 'paypa1'],
            'bank' => ['bank', 'b4nk', 'secure-bank'],
            'netflix' => ['netflix', 'netfl1x', 'netflx'],
            'spotify' => ['spotify', 'sp0tify', 'spotfy'],
            'ebay' => ['ebay', '3bay', 'ebey'],
            'chase' => ['chase', 'ch4se', 'chasebank'],
            'wells' => ['wells', 'w3lls', 'wellsfargo'],
            'bofa' => ['bofa', 'bankofamerica'],
            'citibank' => ['citi', 'c1ti', 'citibank'],
            'mastercard' => ['mastercard', 'master-card'],
            'visa' => ['visa', 'v1sa', 'visacard']
        ];

        $urlLower = strtolower($url);
        $indicators = [];
        $riskScore = 0;

        foreach ($brands as $brand => $variations) {
            foreach ($variations as $variation) {
                if (strpos($urlLower, $variation) !== false) {
                    $indicators[] = "Brand impersonation: contains '{$variation}' (pretending to be '{$brand}')";
                    $riskScore += 20;
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
     * Analyze clickable image
     */
    protected function analyzeImage(string $url, array $imageData): array
    {
        $indicators = [];
        $riskScore = 0;

        $imgSrc = $imageData['img_src'] ?? '';
        $altText = $imageData['alt_text'] ?? '';
        $imgTitle = $imageData['img_title'] ?? '';

        // Check alt text for phishing keywords
        if ($altText) {
            $phishingKeywords = ['verify', 'confirm', 'login', 'secure', 'account', 'password', 'update', 'click'];
            $altLower = strtolower($altText);
            foreach ($phishingKeywords as $keyword) {
                if (strpos($altLower, $keyword) !== false) {
                    $indicators[] = "Image alt text contains phishing keyword: '{$keyword}'";
                    $riskScore += 15;
                    break;
                }
            }
        }

        // Check if image is a button/CTA
        if (preg_match('/button|btn|click|submit|go|continue|proceed|sign|login/i', $altText . ' ' . $imgTitle)) {
            $indicators[] = "Image is a call-to-action button (common in phishing)";
            $riskScore += 10;
        }

        // Check image source domain
        if ($imgSrc) {
            $imageDomain = parse_url($imgSrc, PHP_URL_HOST);
            $urlDomain = parse_url($url, PHP_URL_HOST);

            if ($imageDomain && $urlDomain && $imageDomain !== $urlDomain) {
                $indicators[] = "Image hosted on different domain: '{$imageDomain}' vs URL domain '{$urlDomain}'";
                $riskScore += 20;
            }

            // Check for suspicious image hosting
            $suspiciousImageHosts = ['imgur.com', 'ibb.co', 'postimg.cc', 'tinypic.com', 'imageshack.com'];
            foreach ($suspiciousImageHosts as $host) {
                if (strpos($imageDomain, $host) !== false) {
                    $indicators[] = "Image hosted on suspicious site: '{$host}'";
                    $riskScore += 15;
                    break;
                }
            }
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Analyze link structure
     */
    protected function analyzeLinkStructure(string $url): array
    {
        $indicators = [];
        $riskScore = 0;

        // Check for @ symbol in URL (can hide destination)
        if (strpos($url, '@') !== false) {
            $indicators[] = "URL contains @ symbol (can hide true destination)";
            $riskScore += 20;
        }

        // Check for multiple slashes (obfuscation)
        if (substr_count($url, '/') > 5) {
            $indicators[] = "URL contains excessive slashes (possible obfuscation)";
            $riskScore += 10;
        }

        // Check for percent encoding
        if (preg_match('/%[0-9a-f]{2}/i', $url)) {
            $indicators[] = "URL contains percent-encoding (possible obfuscation)";
            $riskScore += 10;
        }

        // Check for hyphens (often used in phishing domains)
        if (substr_count($url, '-') > 3) {
            $indicators[] = "URL contains excessive hyphens (common in phishing)";
            $riskScore += 10;
        }

        return [
            'riskScore' => $riskScore,
            'indicators' => $indicators
        ];
    }

    /**
     * Get severity level based on risk score
     */
    protected function getSeverity(int $score): string
    {
        if ($score >= 70) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        }
        return 'low';
    }
}