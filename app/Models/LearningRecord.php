<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningRecord extends Model
{
    protected $table = 'practice_attempts';

    protected $fillable = [
        'user_id', 'scenario_id', 'scenario_url', 'model_prediction',
        'student_decision', 'model_confidence', 'shap_risk_indicators',
        'misconceptions', 'targeted_practice', 'llm_explanation',
        'mastery_achieved', 'session_type', 'attempts'
    ];

    protected $casts = [
        'model_prediction'     => 'boolean',
        'student_decision'     => 'boolean',
        'mastery_achieved'     => 'boolean',
        'shap_risk_indicators' => 'array',
        'misconceptions'       => 'array',
        'targeted_practice'    => 'array',
        'attempts'             => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ⚠️ CRITICAL: Required for accurate category detection
    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }
}