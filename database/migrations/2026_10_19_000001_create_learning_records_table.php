<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('scenario_url', 2048);
            $table->boolean('model_prediction');
            $table->boolean('student_decision');
            $table->decimal('model_confidence', 10, 4);
            $table->json('shap_risk_indicators')->nullable();
            $table->json('misconceptions')->nullable();
            $table->json('targeted_practice')->nullable();
            $table->text('llm_explanation')->nullable();
            $table->boolean('mastery_achieved')->default(false);
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_records');
    }
};