<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ThreatIntelligenceService;

echo "Testing AbuseIPDB...\n\n";

$service = new ThreatIntelligenceService();
$result = $service->checkAbuseIPDB('8.8.8.8');

echo "Result:\n";
print_r($result);

echo "\nDone.\n";
