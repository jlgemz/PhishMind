<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{

    protected $fillable = [
        'name', 'email', 'password', 'otp_code', 'otp_expires_at', 
        'is_verified', 'is_admin', 'diagnostic_completed_at',
        'reset_otp', 'reset_otp_expires_at',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime', 
        'is_verified' => 'boolean',
        'diagnostic_completed_at' => 'datetime',
        'reset_otp_expires_at' => 'datetime',
    ];

    public function learningProfiles()
    {
        return $this->hasMany(LearningProfile::class);
    }

    public function practiceAttempts()
    {
        return $this->hasMany(LearningRecord::class);
    }

    public function getLastPracticeAtAttribute()
    {
        return $this->practiceAttempts()->latest()->value('created_at');
    }
}