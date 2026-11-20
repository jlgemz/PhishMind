<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->string('indicator')->nullable(); // e.g., 'urgency', 'deceptive_url'
            $table->boolean('is_diagnostic')->default(false);
            $table->string('status')->default('approved'); // draft, approved
        });

        // Rename learning_records to practice_attempts to match your spec
        // NOTE: If you already have data in learning_records, use `rename` instead
        Schema::rename('learning_records', 'practice_attempts');
        
        Schema::table('practice_attempts', function (Blueprint $table) {
            $table->string('session_type')->default('practice'); // diagnostic, refresher, practice
            $table->foreignId('scenario_id')->nullable()->constrained();
        });
    }

    public function down(): void {
        Schema::table('practice_attempts', function (Blueprint $table) {
            $table->dropColumn('session_type');
            $table->dropForeign(['scenario_id']);
            $table->dropColumn('scenario_id');
        });
        Schema::rename('practice_attempts', 'learning_records');
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn(['indicator', 'is_diagnostic', 'status']);
        });
    }
};