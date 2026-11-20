<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ThreatIntelligenceService;

echo "Testing VirusTotal API...\n\n";

$service = new ThreatIntelligenceService();

// Test with a safe URL
echo "Testing safe URL (google.com):\n";
$result = $service->checkVirusTotal('https://google.com');
print_r($result);

echo "\n\nTesting with a test URL:\n";
$result = $service->checkVirusTotal('http://test.com');
print_r($result);

echo "\nDone.\n";
