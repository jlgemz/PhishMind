<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // URL / Web Phishing, Email Phishing, etc.
            $table->text('content'); // The URL or Email/SMS body
            $table->boolean('is_phishing')->default(false);
            $table->text('explanation')->nullable();
            $table->json('risk_indicators')->nullable();
            $table->string('misconception_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};