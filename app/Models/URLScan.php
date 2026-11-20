<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class URLScan extends Model
{
    use HasFactory;

    protected $table = 'url_scans';

    protected $fillable = [
        'url',
        'domain',
        'status',
        'confidence_score',
        'scan_results',
        'ip_address',
        'sender_email',
        'email_subject',
        'email_domain',
        'detection_source',
        'image_url',
        'image_alt',
        'email_metadata',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'email_metadata' => 'array',
        'scan_results' => 'array',
        'confidence_score' => 'float'
    ];

    // Status checks
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

    // Source checks - with column existence check
    public function isFromEmail(): bool
    {
        if (!Schema::hasColumn('url_scans', 'detection_source')) {
            return false;
        }
        return $this->detection_source === 'email';
    }

    public function isFromWeb(): bool
    {
        if (!Schema::hasColumn('url_scans', 'detection_source')) {
            return true;
        }
        return $this->detection_source === 'web';
    }

    // Image checks
    public function hasImage(): bool
    {
        if (!Schema::hasColumn('url_scans', 'image_url')) {
            return false;
        }
        return !empty($this->image_url);
    }

    // Get email context
    public function getEmailContext(): array
    {
        $context = [];

        if (Schema::hasColumn('url_scans', 'sender_email')) {
            $context['sender'] = $this->sender_email;
        }
        if (Schema::hasColumn('url_scans', 'email_subject')) {
            $context['subject'] = $this->email_subject;
        }
        if (Schema::hasColumn('url_scans', 'email_domain')) {
            $context['domain'] = $this->email_domain;
        }
        if (Schema::hasColumn('url_scans', 'image_url')) {
            $context['image'] = $this->image_url;
        }
        if (Schema::hasColumn('url_scans', 'image_alt')) {
            $context['image_alt'] = $this->image_alt;
        }
        if (Schema::hasColumn('url_scans', 'email_metadata')) {
            $context['metadata'] = $this->email_metadata;
        }

        return $context;
    }
}