<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('practice_attempts', function (Blueprint $table) {
            $table->dropForeign(['scenario_id']);
            $table->foreign('scenario_id')
                ->references('id')
                ->on('scenarios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_attempts', function (Blueprint $table) {
            $table->dropForeign(['scenario_id']);
            $table->foreign('scenario_id')
                ->references('id')
                ->on('scenarios')
                ->restrictOnDelete();
        });
    }
};
