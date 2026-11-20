<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LearningProfile extends Model {
    protected $fillable = ['user_id', 'indicator', 'correct_count', 'total_count', 'last_practiced_at'];
    protected $casts = ['last_practiced_at' => 'datetime'];
}