<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SystemStatusController extends Controller
{
    public function index()
    {
        // Check ML Service (port 8001)
        $mlStatus = $this->checkService('http://127.0.0.1:8001/');
        
        // Check Ollama (port 11501 or 11434)
        $ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11501/');
        $ollamaStatus = $this->checkService($ollamaUrl);
        if (!$ollamaStatus['online']) {
            $fallbackUrl = str_contains($ollamaUrl, '11501') ? 'http://127.0.0.1:11434/' : 'http://127.0.0.1:11501/';
            $fallbackStatus = $this->checkService($fallbackUrl);
            if ($fallbackStatus['online']) {
                $ollamaStatus = $fallbackStatus;
            }
        }

        return view('system_status', compact('mlStatus', 'ollamaStatus'));
    }

    private function checkService($url)
    {
        try {
            $response = Http::timeout(3)->get($url);
            if ($response->successful()) {
                return [
                    'online' => true,
                    'message' => 'Online',
                    'data' => $response->json()
                ];
            }
            return [
                'online' => false,
                'message' => 'Error: HTTP ' . $response->status(),
                'data' => null
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'message' => 'Offline (Connection Refused)',
                'data' => null
            ];
        }
    }
}