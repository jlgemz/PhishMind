<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('learning_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('indicator'); // 'urgency', 'deceptive_url', 'impersonation'
            $table->integer('correct_count')->default(0);
            $table->integer('total_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'indicator']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'refresher_interval_days', 'value' => '30'],
            ['key' => 'refresher_question_count', 'value' => '5'],
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('learning_profiles');
    }
};