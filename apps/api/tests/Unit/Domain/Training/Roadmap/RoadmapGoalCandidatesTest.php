<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

class RoadmapGoalCandidatesTest extends TestCase
{
    public function test_advanced_athlete_gets_skill_candidates_while_owned_basics_become_foundation(): void
    {
        $suggestions = CalisthenicsRoadmapSuggester::suggestFromAthleteData($this->athleteData([
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes', 'box_bench'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 32],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 18],
                'squat' => ['barbell_load_value' => 120, 'barbell_reps' => 5],
                'lower_body' => ['variant' => 'split_squat', 'load_value' => null, 'load_unit' => 'kg', 'reps' => 16],
                'hollow_hold_seconds' => 50,
                'passive_hang_seconds' => 60,
                'top_support_hold_seconds' => 45,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_handstand', 'best_hold_seconds' => 20],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 15],
            ],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'clear',
                'pancake_compression' => 'clear',
                'shoulder_extension' => 'clear',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ]));

        $primarySkills = array_column($suggestions['goal_candidates']['primary'], 'skill');
        $foundation = $this->candidatesBySkill($suggestions['goal_candidates']['foundation']);
        $secondarySkills = array_column($suggestions['goal_candidates']['secondary'], 'skill');
        $accessorySkills = array_column($suggestions['goal_candidates']['accessories'], 'skill');

        $this->assertContains('front_lever', $primarySkills);
        $this->assertContains('handstand', array_values(array_unique([...$secondarySkills, ...$accessorySkills, ...$primarySkills])));
        $this->assertNotContains('strict_push_up', $primarySkills);
        $this->assertNotContains('strict_pull_up', $primarySkills);
        $this->assertNotContains('strict_dip', $primarySkills);
        $this->assertSame('owned_foundation', $foundation['strict_push_up']['role']);
        $this->assertSame('owned_foundation', $foundation['strict_pull_up']['role']);
        $this->assertSame('owned_foundation', $foundation['strict_dip']['role']);
    }

    public function test_beginner_gets_foundational_bridge_candidates_instead_of_advanced_targets(): void
    {
        $suggestions = CalisthenicsRoadmapSuggester::suggestFromAthleteData($this->athleteData([
            'available_equipment' => ['rings'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'pull_ups' => ['max_strict_reps' => 0, 'fallback_variant' => 'eccentric'],
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'assisted'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 6],
                'squat' => ['barbell_load_value' => null, 'barbell_reps' => null],
                'lower_body' => ['variant' => 'bodyweight_squat', 'load_value' => null, 'load_unit' => 'kg', 'reps' => 12],
                'hollow_hold_seconds' => 12,
                'passive_hang_seconds' => 15,
                'top_support_hold_seconds' => 5,
            ],
        ]));

        $primarySkills = array_column($suggestions['goal_candidates']['primary'], 'skill');
        $foundation = $this->candidatesBySkill($suggestions['goal_candidates']['foundation']);
        $futureSkills = array_column($suggestions['goal_candidates']['future'], 'skill');

        $this->assertContains('strict_push_up', $primarySkills);
        $this->assertContains('strict_pull_up', $primarySkills);
        $this->assertSame('foundation_bridge', $foundation['strict_push_up']['role']);
        $this->assertSame('foundation_bridge', $foundation['strict_pull_up']['role']);
        $this->assertContains('planche', $futureSkills);
        $this->assertContains('one_arm_pull_up', $futureSkills);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function athleteData(array $overrides): array
    {
        return array_replace_recursive([
            'age_years' => 29,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'maintaining',
            'training_age_months' => 24,
            'weekly_session_goal' => 4,
            'available_equipment' => [],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => null],
                'pull_ups' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'squat' => ['barbell_load_value' => null, 'barbell_reps' => null],
                'rows' => ['variant' => 'bodyweight_row', 'max_reps' => null],
                'lower_body' => ['variant' => 'bodyweight_squat', 'load_value' => null, 'load_unit' => 'kg', 'reps' => null],
                'hollow_hold_seconds' => null,
                'passive_hang_seconds' => null,
                'top_support_hold_seconds' => null,
            ],
            'skill_statuses' => [],
            'mobility_checks' => [],
            'weighted_baselines' => ['experience' => 'none', 'unit' => 'kg', 'movements' => []],
            'pain_level' => null,
            'pain_flags' => [],
            'primary_target_skill' => null,
            'secondary_target_skills' => [],
            'long_term_target_skills' => [],
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return array<string, array<string, mixed>>
     */
    private function candidatesBySkill(array $candidates): array
    {
        $indexed = [];

        foreach ($candidates as $candidate) {
            $indexed[(string) $candidate['skill']] = $candidate;
        }

        return $indexed;
    }
}
