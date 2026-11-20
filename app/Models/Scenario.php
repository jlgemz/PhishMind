<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    protected $fillable = [
        'type', 'content', 'is_phishing', 'explanation', 
        'risk_indicators', 'misconception_key',
        'indicator', 'is_diagnostic', 'status' 
    ];
    
    protected $casts = [
        'is_phishing' => 'boolean',
        'is_diagnostic' => 'boolean',
        'risk_indicators' => 'array',
    ];
}