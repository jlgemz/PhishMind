#!/bin/bash

cd /Users/fdc.kevina-nc-web/phishguard

echo "🔧 Fixing PhishGuard..."

# Fix the model with explicit table name
cat > app/Models/URLScan.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class URLScan extends Model
{
    use HasFactory;

    protected $table = 'url_scans';

    protected $fillable = [
        'url', 'domain', 'status', 'confidence_score', 
        'scan_results', 'ip_address', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'confidence_score' => 'float',
        'scan_results' => 'array'
    ];

    public function isSafe(): bool
    {
        return $this->status === 'safe';
    }

    public function isSuspicious(): bool
    {
        return $this->status === 'suspicious';
    }

    public function isDangerous(): bool
    {
        return $this->status === 'dangerous';
    }
}
EOF

# Fix the controller
cat > app/Http/Controllers/PhishGuardController.php << 'EOF'
<?php

namespace App\Http\Controllers;

use App\Models\URLScan;
use App\Services\MLService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhishGuardController extends Controller
{
    protected $mlService;

    public function __construct(MLService $mlService)
    {
        $this->mlService = $mlService;
    }

    public function index()
    {
        $stats = [
            'total_scans' => URLScan::count(),
            'safe_urls' => URLScan::where('status', 'safe')->count(),
            'suspicious_urls' => URLScan::where('status', 'suspicious')->count(),
            'dangerous_urls' => URLScan::where('status', 'dangerous')->count(),
            'ml_available' => $this->mlService->isHealthy()
        ];

        return view('phishguard.index', compact('stats'));
    }

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid URL provided'
            ], 422);
        }

        try {
            $url = $request->input('url');
            
            $existingScan = URLScan::where('url', $url)
                ->where('created_at', '>', now()->subHours(24))
                ->first();
            
            if ($existingScan) {
                return response()->json([
                    'status' => 'success',
                    'data' => $this->formatScanResult($existingScan),
                    'cached' => true
                ]);
            }

            $mlResult = $this->mlService->analyze($url);
            
            $status = 'safe';
            $confidence = 0;
            
            if (isset($mlResult['prediction'])) {
                $confidence = $mlResult['prediction'] === 1 ? 
                    $mlResult['confidence'] * 100 : 
                    (1 - $mlResult['confidence']) * 100;
                
                if ($confidence > 70) {
                    $status = 'dangerous';
                } elseif ($confidence > 40) {
                    $status = 'suspicious';
                }
            }

            $scan = URLScan::create([
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST) ?? $url,
                'status' => $status,
                'confidence_score' => $confidence,
                'scan_results' => ['ml_analysis' => $mlResult],
                'metadata' => [
                    'ml_result' => $mlResult,
                    'scanned_at' => now()->toDateTimeString()
                ]
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $this->formatScanResult($scan),
                'cached' => false,
                'ml_analysis' => $mlResult
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to scan URL: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function formatScanResult(URLScan $scan): array
    {
        return [
            'id' => $scan->id,
            'url' => $scan->url,
            'domain' => $scan->domain,
            'status' => $scan->status,
            'confidence_score' => $scan->confidence_score,
            'risk_indicators' => $scan->scan_results,
            'scanned_at' => $scan->created_at->format('Y-m-d H:i:s'),
            'status_color' => $this->getStatusColor($scan->status),
            'icon' => $this->getStatusIcon($scan->status)
        ];
    }

    protected function getStatusColor(string $status): string
    {
        return match($status) {
            'safe' => 'success',
            'suspicious' => 'warning',
            'dangerous' => 'danger',
            default => 'secondary'
        };
    }

    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'safe' => 'shield-check',
            'suspicious' => 'exclamation-triangle',
            'dangerous' => 'x-circle',
            default => 'question-circle'
        };
    }
}
EOF

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run migration if table doesn't exist
php artisan migrate

echo "✅ Fix complete! Starting Laravel..."
php artisan serve --host=0.0.0.0 --port=8000