<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('url_scans', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('url_scans', 'sender_email')) {
                $table->string('sender_email')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('url_scans', 'email_subject')) {
                $table->string('email_subject')->nullable()->after('sender_email');
            }
            if (!Schema::hasColumn('url_scans', 'email_domain')) {
                $table->string('email_domain')->nullable()->after('email_subject');
            }
            if (!Schema::hasColumn('url_scans', 'detection_source')) {
                $table->string('detection_source')->default('web')->after('email_domain');
            }
            if (!Schema::hasColumn('url_scans', 'image_url')) {
                $table->string('image_url')->nullable()->after('detection_source');
            }
            if (!Schema::hasColumn('url_scans', 'image_alt')) {
                $table->string('image_alt')->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('url_scans', 'email_metadata')) {
                $table->json('email_metadata')->nullable()->after('image_alt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('url_scans', function (Blueprint $table) {
            $table->dropColumn([
                'sender_email',
                'email_subject',
                'email_domain',
                'detection_source',
                'image_url',
                'image_alt',
                'email_metadata'
            ]);
        });
    }
};