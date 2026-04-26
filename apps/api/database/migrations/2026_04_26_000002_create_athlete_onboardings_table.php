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
        Schema::create('athlete_onboardings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_goal', 64)->nullable();
            $table->json('secondary_goals');
            $table->json('target_skills');
            $table->json('available_equipment');
            $table->json('training_locations');
            $table->json('preferred_training_days');
            $table->unsignedSmallInteger('preferred_session_minutes')->nullable();
            $table->unsignedTinyInteger('weekly_session_goal')->nullable();
            $table->string('preferred_training_time', 32)->default('flexible');
            $table->json('current_level_tests');
            $table->json('skill_statuses');
            $table->unsignedTinyInteger('readiness_rating')->nullable();
            $table->unsignedTinyInteger('sleep_quality')->nullable();
            $table->unsignedTinyInteger('soreness_level')->nullable();
            $table->unsignedTinyInteger('pain_level')->nullable();
            $table->json('pain_areas');
            $table->text('pain_notes')->nullable();
            $table->string('starter_plan_key', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athlete_onboardings');
    }
};
