<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('url_scans', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('domain');
            $table->string('status')->default('pending');
            $table->float('confidence_score')->default(0);
            $table->text('scan_results')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['url', 'status']);
            $table->unique('url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_scans');
    }
};