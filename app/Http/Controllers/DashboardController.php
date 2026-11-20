<?php

namespace App\Http\Controllers;

use App\Models\LearningRecord;
use App\Models\LearningProfile;
use App\Services\RefresherService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $refresher;

    public function __construct(RefresherService $refresher)
    {
        $this->refresher = $refresher;
    }

    public function index()
    {
        $userId = auth()->id();

        // Eager load scenario relationship for accurate category detection
        $records = LearningRecord::with('scenario')
            ->where('user_id', $userId)
            ->get();

        $totalAttempts   = $records->count();
        $correctAttempts = $records->where('mastery_achieved', true)->count();
        $accuracy        = $totalAttempts > 0
            ? round(($correctAttempts / $totalAttempts) * 100) : 0;

        $recentRecord = $records->sortByDesc('created_at')->first();

        // 3 categories using REAL scenario type from DB
        $progress = [
            'URL / Web Phishing' => [
                'total'   => $records->filter(fn($r) => $this->getType($r) === 'URL / Web Phishing')->count(),
                'correct' => $records->filter(fn($r) => $this->getType($r) === 'URL / Web Phishing' && $r->mastery_achieved)->count(),
            ],
            'Email Phishing' => [
                'total'   => $records->filter(fn($r) => $this->getType($r) === 'Email Phishing')->count(),
                'correct' => $records->filter(fn($r) => $this->getType($r) === 'Email Phishing' && $r->mastery_achieved)->count(),
            ],
            'SMS / Smishing' => [
                'total'   => $records->filter(fn($r) => $this->getType($r) === 'SMS Phishing')->count(),
                'correct' => $records->filter(fn($r) => $this->getType($r) === 'SMS Phishing' && $r->mastery_achieved)->count(),
            ],
        ];

        $stats = [
            'total_complete'   => $totalAttempts,
            'accuracy'         => $accuracy,
            'progress'         => $progress,
            'recent'           => $recentRecord,
            'refresher_due'    => $this->refresher->isRefresherDue($userId),
            'needs_diagnostic' => $this->refresher->needsDiagnostic($userId),
        ];

        return view('dashboard.index', compact('stats'));
    }

    public function profile()
    {
        $userId = auth()->id();

        // Eager load scenario relationship
        $records = LearningRecord::with('scenario')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $totalAttempts   = $records->count();
        $correctAttempts = $records->where('mastery_achieved', true)->count();
        $accuracy        = $totalAttempts > 0
            ? round(($correctAttempts / $totalAttempts) * 100) : 0;

        // 3 categories using REAL scenario type from DB
        $progress = [
            'URL / Web Phishing' => [
                'correct' => $records->filter(fn($r) => $this->getType($r) === 'URL / Web Phishing' && $r->mastery_achieved)->count(),
                'total'   => $records->filter(fn($r) => $this->getType($r) === 'URL / Web Phishing')->count(),
            ],
            'Email Phishing' => [
                'correct' => $records->filter(fn($r) => $this->getType($r) === 'Email Phishing' && $r->mastery_achieved)->count(),
                'total'   => $records->filter(fn($r) => $this->getType($r) === 'Email Phishing')->count(),
            ],
            'SMS / Smishing' => [
                'correct' => $records->filter(fn($r) => $this->getType($r) === 'SMS Phishing' && $r->mastery_achieved)->count(),
                'total'   => $records->filter(fn($r) => $this->getType($r) === 'SMS Phishing')->count(),
            ],
        ];

        $indicatorLabels = [
            'urgency'       => 'Urgency & Pressure Tactics',
            'deceptive_url' => 'Deceptive URLs',
        ];
        $indicatorStats = [];

        foreach ($indicatorLabels as $key => $label) {
            $profile = LearningProfile::where('user_id', $userId)
                ->where('indicator', $key)
                ->first();

            $indicatorStats[$label] = [
                'correct' => $profile->correct_count ?? 0,
                'total'   => $profile->total_count ?? 0,
                'rate'    => ($profile && $profile->total_count > 0)
                    ? round(($profile->correct_count / $profile->total_count) * 100)
                    : 0,
            ];
        }

        $allMisconceptions = $records->pluck('misconceptions')->filter()->flatten(1);
        $mostCommonMisconception = $allMisconceptions->count() > 0
            ? $allMisconceptions->groupBy('name')->sortByDesc(fn($g) => $g->count())->keys()->first()
            : null;

        $stats = compact(
            'totalAttempts',
            'correctAttempts',
            'accuracy',
            'progress',
            'indicatorStats',
            'mostCommonMisconception'
        );

        return view('dashboard.profile', compact('records', 'stats'));
    }

    /**
     * Read the REAL scenario type from the database.
     * Falls back to heuristics only for old records without scenario_id.
     */
    private function getType(LearningRecord $record): ?string
    {
        if ($record->scenario && $record->scenario->type) {
            return $record->scenario->type;
        }

        // Fallback for records without scenario_id
        $content = $record->scenario_url;
        if (!$content) return null;

        if (str_starts_with($content, 'http://') || str_starts_with($content, 'https://')) {
            return 'URL / Web Phishing';
        }

        return 'Email Phishing';
    }
}