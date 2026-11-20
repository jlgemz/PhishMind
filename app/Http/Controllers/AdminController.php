<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LearningRecord;
use App\Models\Scenario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students'  => User::where('is_admin', false)->count(),
            'total_scenarios' => Scenario::count(),
            'total_attempts'  => LearningRecord::count(),
            'global_mastery'  => LearningRecord::where('mastery_achieved', true)->count(),
        ];

        $mlUrl = $this->getMlServiceUrl();
        $mlStatus = $this->pingService($mlUrl . '/');
        $ollamaStatus = $this->pingOllamaService();

        return view('admin.dashboard', compact('stats', 'mlStatus', 'ollamaStatus'));
    }

    public function uploadDataset(Request $request)
    {
        $request->validate([
            'dataset_type' => 'required|in:url,email,sms',
            'file'         => 'required|file|max:500000',
        ]);

        $file = $request->file('file');

        // 👇 VALIDATE HEADERS BEFORE UPLOADING
        $validation = $this->validateDatasetHeaders($file->getRealPath(), $request->dataset_type);
        if ($validation !== true) {
            return back()->with('error', $validation);
        }

        try {
            $fileStream = fopen($file->getRealPath(), 'r');

            $mlUrl = $this->getMlServiceUrl();
            $response = Http::timeout(600)->attach(
                'file', $fileStream, $file->getClientOriginalName()
            )->post("{$mlUrl}/admin/upload-dataset", [
                'dataset_type' => $request->dataset_type,
            ]);

            fclose($fileStream);

            if ($response->successful()) {
                return back()->with('success', 'Dataset uploaded. Click "Train Model" to apply.');
            }
            return back()->with('error', 'Python error: ' . ($response->json('detail') ?? $response->body()));
        } catch (\Exception $e) {
            return back()->with('error', 'Connection error: ' . $e->getMessage());
        }
    }

    public function trainModel(Request $request)
    {
        $request->validate([
            'model_type' => 'required|in:url,email,sms',
        ]);

        try {
            $endpoint = match ($request->model_type) {
                'url'   => 'train-url',
                'email' => 'train-email',
                'sms'   => 'train-sms',
            };

            $csvPath = match ($request->model_type) {
                'url'   => $this->findUrlDataset(),
                'email' => base_path('ml_service/email_dataset.csv'),
                'sms'   => base_path('ml_service/sms_dataset.csv'),
            };

            // 👇 VALIDATE HEADERS BEFORE TRAINING
            if (file_exists($csvPath)) {
                $validation = $this->validateDatasetHeaders($csvPath, $request->model_type);
                if ($validation !== true) {
                    return response()->json([
                        'success' => false,
                        'message' => $validation,
                    ], 400);
                }
            }

            $mlUrl = $this->getMlServiceUrl();
            $response = Http::timeout(600)->post("{$mlUrl}/admin/{$endpoint}");
            $body = $response->json();

            if (!is_array($body)) {
                Log::error('Python returned non-JSON', ['body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Python server returned an invalid response. Check the terminal for errors.',
                ], 500);
            }

            if (!$response->successful() || ($body['status'] ?? null) !== 'success') {
                $errorDetail = $body['detail'] ?? $body['message'] ?? $response->body();
                Log::error('Training failed', ['endpoint' => $endpoint, 'detail' => $errorDetail]);
                return response()->json([
                    'success' => false,
                    'message' => is_string($errorDetail) ? $errorDetail : json_encode($errorDetail),
                ], 500);
            }

            $inserted = 0;
            $type = match ($request->model_type) {
                'url'   => 'URL / Web Phishing',
                'email' => 'Email Phishing',
                'sms'   => 'SMS Phishing',
            };

            $indicator = match ($request->model_type) {
                'url'   => 'deceptive_url',
                'email' => 'urgency',
                'sms'   => 'urgency',
            };

            if (file_exists($csvPath)) {
                // Safely detach referencing practice attempts before deleting old scenarios of this type
                LearningRecord::whereIn('scenario_id', function ($query) use ($type) {
                    $query->select('id')->from('scenarios')->where('type', $type);
                })->update(['scenario_id' => null]);

                Scenario::where('type', $type)->delete();

                $file = fopen($csvPath, 'r');
                $firstLine = fgets($file);
                rewind($file);

                if (str_contains($firstLine, ';')) {
                    $delimiter = ';';
                } elseif (str_contains($firstLine, "\t")) {
                    $delimiter = "\t";
                } else {
                    $delimiter = ',';
                }

                $headers = fgetcsv($file, 0, $delimiter, '"');
                $textIdx = null;
                $labelIdx = null;

                if ($headers) {
                    foreach ($headers as $i => $col) {
                        $col = strtolower(trim($col, " \"'\xEF\xBB\xBF"));
                        
                        if (in_array($col, ['url', 'text', 'message', 'email text', 'body', 'content', 'v2', 'cleaned_message', 'original_message'])) {
                            $textIdx = $i;
                        }
                        
                        if (in_array($col, ['label', 'class', 'target', 'email type', 'etiqueta', 'v1', 'final_label', 'verified_label', 'original_label'])) {
                            $labelIdx = $i;
                        }
                    }
                }

                if ($textIdx !== null && $labelIdx !== null) {
                    while (($row = fgetcsv($file, 0, $delimiter, '"')) !== false) {
                        if (!isset($row[$textIdx]) || !isset($row[$labelIdx])) continue;

                        $content = trim($row[$textIdx]);
                        $label = strtolower(trim($row[$labelIdx]));
                        if (empty($content)) continue;

                        $isPhishing = null;

                        if (str_contains($label, 'phishing') || str_contains($label, 'malicious') || str_contains($label, 'spam') || str_contains($label, 'smishing') || $label === '1') {
                            $isPhishing = true;
                        } elseif (str_contains($label, 'legitimate') || str_contains($label, 'benign') || str_contains($label, 'ham') || $label === '0') {
                            $isPhishing = false;
                        }

                        if ($isPhishing === null) continue;

                        Scenario::create([
                            'type' => $type,
                            'content' => substr($content, 0, 60000),
                            'is_phishing' => $isPhishing,
                            'indicator' => $indicator,
                            'status' => 'approved',
                            'is_diagnostic' => false,
                            'explanation' => 'Imported from the ' . strtoupper($request->model_type) . ' dataset.',
                        ]);
                        $inserted++;
                    }
                }
                fclose($file);
            }

            return response()->json([
                'success' => true,
                'message' => $body['message'] . " | Inserted $inserted new scenarios.",
            ]);
        } catch (\Exception $e) {
            Log::error('Training exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 👇 HELPER: Validates that the CSV headers match the expected dataset type
     */
    private function validateDatasetHeaders(string $filePath, string $expectedType)
    {
        $handle = fopen($filePath, 'r');
        $firstLine = fgets($handle);
        rewind($handle);

        if (str_contains($firstLine, ';')) {
            $delimiter = ';';
        } elseif (str_contains($firstLine, "\t")) {
            $delimiter = "\t";
        } else {
            $delimiter = ',';
        }

        $headers = fgetcsv($handle, 0, $delimiter, '"');
        fclose($handle);

        if (!$headers) {
            return "Could not read the CSV file headers.";
        }

        $headers = array_map(function($h) {
            return strtolower(trim($h, " \"'\xEF\xBB\xBF"));
        }, $headers);

        $hasUrl = !empty(array_intersect($headers, ['url', 'link', 'website']));
        $hasEmail = !empty(array_intersect($headers, ['subject', 'sender', 'receiver', 'email text']));
        $hasSms = !empty(array_intersect($headers, ['v1', 'v2', 'sms']));
        $hasText = !empty(array_intersect($headers, ['text', 'message', 'cleaned_message', 'original_message', 'body']));

        if ($expectedType === 'url') {
            if (!$hasUrl || $hasEmail || $hasSms) {
                return "Invalid dataset format for URL. Please upload a CSV containing a 'url' column and ensure it is not an email/SMS dataset.";
            }
        } elseif ($expectedType === 'email') {
            if (!$hasEmail && !$hasText) {
                return "Invalid dataset format for Email. Missing expected email columns (like 'subject', 'body', or 'cleaned_message').";
            }
            if ($hasUrl && !$hasEmail && !$hasText) {
                return "Invalid dataset format. This looks like a URL dataset, not an Email dataset.";
            }
        } elseif ($expectedType === 'sms') {
            if ($hasEmail) {
                return "Invalid dataset format for SMS. This looks like an Email dataset (contains 'subject' or 'sender').";
            }
            if (!$hasSms && !$hasText) {
                return "Invalid dataset format for SMS. Missing expected SMS columns (like 'v2', 'message', or 'cleaned_message').";
            }
        }

        return true; // Valid
    }

    private function findUrlDataset(): string
    {
        foreach (['final_url_dataset.csv', 'url_dataset.csv'] as $name) {
            $path = base_path('ml_service/' . $name);
            if (file_exists($path)) return $path;
        }
        return base_path('ml_service/url_dataset.csv');
    }

    public function export()
    {
        $records = LearningRecord::with('user')->get();
        $filename = 'phishmind_research_data_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Record ID', 'Student Name', 'Student Email', 'Scenario',
                'Model Prediction', 'Student Decision', 'Confidence',
                'Mastery Achieved', 'Date',
            ]);

            foreach ($records as $record) {
                fputcsv($file, [
                    $record->id,
                    $record->user->name ?? 'Unknown',
                    $record->user->email ?? 'Unknown',
                    $record->scenario_url,
                    $record->model_prediction ? 'Phishing' : 'Legitimate',
                    $record->student_decision ? 'Phishing' : 'Legitimate',
                    round($record->model_confidence, 2) . '%',
                    $record->mastery_achieved ? 'Yes' : 'No',
                    $record->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function systemStatus()
    {
        $mlUrl = $this->getMlServiceUrl();
        $mlStatus = $this->pingService($mlUrl . '/');
        $ollamaStatus = $this->pingOllamaService();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'ml_online' => $mlStatus['online'],
                'ollama_online' => $ollamaStatus['online'],
                'ollama_url' => $ollamaStatus['url'] ?? null,
            ]);
        }

        return view('admin.system_status', compact('mlStatus', 'ollamaStatus'));
    }

    public function toggleService(Request $request)
    {
        $request->validate([
            'service' => 'required|in:ml,ollama',
            'action'  => 'required|in:start,stop',
        ]);

        $service = $request->service;
        $action = $request->action;
        $pythonPath = base_path('ml_service');
        $isWindows = PHP_OS_FAMILY === 'Windows';

        try {
            if ($service === 'ml') {
                if ($isWindows) {
                    exec('powershell -Command "$p = (Get-NetTCPConnection -LocalPort 8001 -ErrorAction SilentlyContinue).OwningProcess; if ($p) { Stop-Process -Id $p -Force }"');
                } else {
                    exec('pkill -f "python3 app.py"');
                }
                sleep(1);

                if ($action === 'start') {
                    if ($isWindows) {
                        pclose(popen("cd /d \"{$pythonPath}\" && start /B python app.py > ml_service.log 2>&1", "r"));
                    } else {
                        $cmd = "cd {$pythonPath} && nohup python3 app.py > ml_service.log 2>&1 &";
                        exec($cmd);
                    }
                }
            } elseif ($service === 'ollama') {
                if ($action === 'start') {
                    if ($isWindows) {
                        pclose(popen("start /B ollama serve > ollama.log 2>&1", "r"));
                    } else {
                        exec('nohup ollama serve > ollama.log 2>&1 &');
                    }
                } else {
                    if ($isWindows) {
                        exec('taskkill /F /IM ollama.exe /T 2>&1');
                        exec('taskkill /F /IM "ollama app.exe" 2>&1');
                    } else {
                        exec('pkill -f "ollama serve"');
                    }
                }
            }

            sleep(2);

            return response()->json([
                'success' => true, 
                'message' => "Successfully executed {$action} for {$service}."
            ]);
        } catch (\Exception $e) {
            Log::error("Service toggle error: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getMlServiceUrl(): string
    {
        $url = config('services.ml_service.url', env('ML_SERVICE_URL', 'http://127.0.0.1:8001'));
        return rtrim($url, '/');
    }

    public function getOllamaUrl(): string
    {
        $configured = env('OLLAMA_URL') ?: env('OLLAMA_HOST') ?: getenv('OLLAMA_HOST');
        if ($configured) {
            if (!str_starts_with($configured, 'http://') && !str_starts_with($configured, 'https://')) {
                $configured = 'http://' . $configured;
            }
            $configured = str_replace('0.0.0.0', '127.0.0.1', $configured);
            return rtrim($configured, '/');
        }

        return 'http://127.0.0.1:11501';
    }

    public function pingOllamaService(): array
    {
        $primaryUrl = $this->getOllamaUrl();
        $status = $this->pingService($primaryUrl . '/');

        if (!$status['online']) {
            $fallbackUrl = str_contains($primaryUrl, '11501') ? 'http://127.0.0.1:11434' : 'http://127.0.0.1:11501';
            $fallbackStatus = $this->pingService($fallbackUrl . '/');
            if ($fallbackStatus['online']) {
                $fallbackStatus['url'] = $fallbackUrl;
                return $fallbackStatus;
            }
        }

        $status['url'] = $primaryUrl;
        return $status;
    }

    private function pingService($url)
    {
        try {
            $response = Http::timeout(2)->get($url);
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