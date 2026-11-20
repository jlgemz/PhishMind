<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ThreatIntelligenceService;

echo "Testing AbuseIPDB API...\n\n";

// Check if API key is set
$apiKey = env('ABUSEIPDB_API_KEY');
if (empty($apiKey)) {
    echo "❌ ABUSEIPDB_API_KEY is not set in .env\n";
    exit(1);
}
echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

$service = new ThreatIntelligenceService();

// Test with Google's DNS (should be clean)
echo "Testing IP 8.8.8.8 (Google DNS - should be clean):\n";
$result = $service->checkAbuseIPDB('8.8.8.8');
print_r($result);

// Test with a known abusive IP (example)
echo "\nTesting IP 1.1.1.1 (Cloudflare DNS):\n";
$result = $service->checkAbuseIPDB('1.1.1.1');
print_r($result);

echo "\nDone.\n";
