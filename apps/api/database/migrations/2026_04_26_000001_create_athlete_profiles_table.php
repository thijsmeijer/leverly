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
        Schema::create('athlete_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 120);
            $table->string('timezone', 80)->default('UTC');
            $table->string('unit_system', 16)->default('metric');
            $table->unsignedSmallInteger('training_age_months')->nullable();
            $table->string('experience_level', 32)->default('new');
            $table->decimal('current_bodyweight_value', 6, 2)->nullable();
            $table->string('bodyweight_unit', 8)->default('kg');
            $table->string('primary_goal', 64)->nullable();
            $table->json('secondary_goals');
            $table->json('target_skills');
            $table->json('available_equipment');
            $table->json('training_locations');
            $table->json('movement_limitations');
            $table->text('injury_notes')->nullable();
            $table->json('preferred_training_days');
            $table->unsignedSmallInteger('preferred_session_minutes')->nullable();
            $table->unsignedTinyInteger('weekly_session_goal')->nullable();
            $table->string('preferred_training_time', 32)->default('flexible');
            $table->string('progression_pace', 32)->default('balanced');
            $table->string('intensity_preference', 32)->default('auto');
            $table->string('effort_tracking_preference', 32)->default('simple');
            $table->string('deload_preference', 32)->default('auto');
            $table->json('session_structure_preferences');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athlete_profiles');
    }
};
