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
            $table->unsignedTinyInteger('age_years')->nullable();
            $table->unsignedSmallInteger('training_age_months')->nullable();
            $table->string('experience_level', 32)->default('new');
            $table->decimal('current_bodyweight_value', 6, 2)->nullable();
            $table->string('bodyweight_unit', 8)->default('kg');
            $table->decimal('height_value', 6, 2)->nullable();
            $table->string('height_unit', 8)->default('cm');
            $table->string('weight_trend', 32)->default('unknown');
            $table->json('prior_sport_background');
            $table->string('primary_goal', 64)->nullable();
            $table->json('secondary_goals');
            $table->json('target_skills');
            $table->string('primary_target_skill', 80)->nullable();
            $table->json('secondary_target_skills');
            $table->json('long_term_target_skills');
            $table->json('base_focus_areas');
            $table->json('goal_modules');
            $table->json('roadmap_suggestions');
            $table->json('available_equipment');
            $table->json('training_locations');
            $table->json('preferred_training_days');
            $table->unsignedSmallInteger('preferred_session_minutes')->nullable();
            $table->unsignedTinyInteger('weekly_session_goal')->nullable();
            $table->json('current_level_tests');
            $table->json('skill_statuses');
            $table->json('mobility_checks');
            $table->json('weighted_baselines');
            $table->unsignedTinyInteger('readiness_rating')->nullable();
            $table->unsignedTinyInteger('sleep_quality')->nullable();
            $table->unsignedTinyInteger('soreness_level')->nullable();
            $table->unsignedTinyInteger('pain_level')->nullable();
            $table->json('pain_areas');
            $table->json('pain_flags');
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
