<?php

namespace App\Services;

use App\Models\Scenario;
use App\Models\LearningProfile;
use App\Models\LearningRecord;
use App\Models\User;

class RefresherService
{
    public function getWeakestIndicator($userId)
    {
        $weakest = LearningProfile::where('user_id', $userId)
            ->where('total_count', '>', 0)
            ->orderByRaw('(correct_count / total_count) ASC')
            ->first();

        return $weakest ? $weakest->indicator : null;
    }

    public function getRefresherQuestions($userId, $count = 5)
    {
        $weakestIndicator = $this->getWeakestIndicator($userId);

        $recentIds = LearningRecord::where('user_id', $userId)
            ->where('created_at', '>', now()->subDays(7))
            ->pluck('scenario_id')
            ->filter();

        $weakQuestions = [];
        $otherQuestions = [];

        if ($weakestIndicator) {
            $weakQuestions = Scenario::where('status', 'approved')
                ->where('indicator', $weakestIndicator)
                ->whereNotIn('id', $recentIds)
                ->inRandomOrder()
                ->take(3)
                ->get();
        }

        $needed = $count - count($weakQuestions);

        $otherQuestions = Scenario::where('status', 'approved')
            ->whereNotIn('id', $recentIds)
            ->whereNotIn('id', $weakQuestions->pluck('id'))
            ->inRandomOrder()
            ->take($needed)
            ->get();

        return $weakQuestions->merge($otherQuestions);
    }

    public function updateProfile($userId, $indicator, $isCorrect)
    {
        if (!$indicator) return;

        $profile = LearningProfile::firstOrCreate(
            ['user_id' => $userId, 'indicator' => $indicator],
            ['correct_count' => 0, 'total_count' => 0]
        );

        $profile->total_count += 1;
        if ($isCorrect) {
            $profile->correct_count += 1;
        }
        $profile->last_practiced_at = now();
        $profile->save();
    }

    public function isRefresherDue($userId)
    {
        $lastPractice = LearningRecord::where('user_id', $userId)->latest()->first();
        if (!$lastPractice) return false;

        $interval = (int) \App\Models\Setting::get('refresher_interval_days', 30);
        return $lastPractice->created_at->diffInDays(now()) >= $interval;
    }

    /**
     * A user needs the diagnostic until they have COMPLETED it
     * (all 5 answers submitted and marked in `diagnostic_completed_at`).
     *
     * This replaces the old "any diagnostic record exists" check, which
     * incorrectly treated a partially-completed diagnostic as finished.
     */
    public function needsDiagnostic($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        return is_null($user->diagnostic_completed_at);
    }
}