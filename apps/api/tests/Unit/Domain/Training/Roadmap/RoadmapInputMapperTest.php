<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInputMapper;
use PHPUnit\Framework\TestCase;

class RoadmapInputMapperTest extends TestCase
{
    public function test_it_maps_athlete_data_into_the_roadmap_input_contract(): void
    {
        $input = RoadmapInputMapper::fromAthleteData([
            'age_years' => 29,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 72.5,
            'bodyweight_unit' => 'kg',
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength'],
            'training_age_months' => 18,
            'experience_level' => 'intermediate',
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 4,
            'preferred_training_days' => ['monday', 'wednesday'],
            'training_locations' => ['home', 'park'],
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes'],
            'pain_level' => 2,
            'pain_areas' => ['wrist'],
            'pain_notes' => 'Wrists need more warm-up.',
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4],
                'dips' => ['max_strict_reps' => 6],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20],
            ],
            'mobility_checks' => ['wrist_extension' => 'limited'],
            'weighted_baselines' => [
                'experience' => 'repetition_work',
                'unit' => 'kg',
                'movements' => [
                    ['movement' => 'weighted_pull_up', 'external_load_value' => 10, 'reps' => 5, 'rir' => 2],
                ],
            ],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['planche'],
        ]);

        $this->assertSame(29, $input->profileContext['age_years']);
        $this->assertSame(18, $input->trainingContext['training_age_months']);
        $this->assertSame(['pull_up_bar', 'rings', 'parallettes'], $input->equipment);
        $this->assertSame(['wrist'], $input->painFlags['areas']);
        $this->assertSame(18, $input->baselineTests['push_ups']['max_strict_reps']);
        $this->assertSame('freestanding_kick_up', $input->goalModules['skill_statuses']['handstand']['status']);
        $this->assertSame('handstand', $input->selectedPrimaryGoal);
        $this->assertSame(['strict_pull_up'], $input->secondaryInterests);
        $this->assertSame(['planche'], $input->longTermAspirations);
    }
}
