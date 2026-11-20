<?php

$apiKey = 'fe99e7e1fe2aeb638bad169e4c8c6911380d584b4564bc4b1958c6632203496d';
$url = 'https://google.com';

echo "Testing VirusTotal API with simple script...\n\n";

// Submit URL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.virustotal.com/api/v3/urls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['url' => $url]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-apikey: ' . $apiKey,
    'Accept: application/json',
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Submit Response Code: " . $httpCode . "\n";
echo "Submit Response: " . $response . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $analysisId = $data['data']['id'] ?? null;
    
    if ($analysisId) {
        echo "Analysis ID: " . $analysisId . "\n";
        echo "Waiting for results...\n";
        sleep(5);
        
        // Get results
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.virustotal.com/api/v3/analyses/' . $analysisId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-apikey: ' . $apiKey,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo "Analysis Results: " . $response . "\n";
    }
} else {
    echo "Error: " . $httpCode . " - " . $response . "\n";
}
