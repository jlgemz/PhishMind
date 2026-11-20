<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ThreatIntelligenceService;

echo "Testing Google Safe Browsing API...\n\n";

$service = new ThreatIntelligenceService();

// Test safe URL
echo "Testing safe URL (google.com):\n";
$result = $service->checkGoogleSafeBrowsing('https://google.com');
print_r($result);

// Test malicious test URL
echo "\nTesting malicious test URL:\n";
$result = $service->checkGoogleSafeBrowsing('http://testsafebrowsing.appspot.com/apiv4/ANY_PLATFORM/MALWARE/URL/');
print_r($result);

echo "\nDone.\n";
