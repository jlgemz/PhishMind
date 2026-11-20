<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('url_scans', function (Blueprint $table) {
            $table->string('sender_email')->nullable()->after('ip_address');
            $table->string('email_subject')->nullable()->after('sender_email');
            $table->string('email_domain')->nullable()->after('email_subject');
            $table->string('detection_source')->default('web')->after('email_domain');
        });
    }

    public function down(): void
    {
        Schema::table('url_scans', function (Blueprint $table) {
            $table->dropColumn(['sender_email', 'email_subject', 'email_domain', 'detection_source']);
        });
    }
};