<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapSkillPortfolioOptimizerTest extends TestCase
{
    public function test_three_day_beginner_reserves_foundation_and_keeps_advanced_goal_future(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'weekly_session_goal' => 3,
            'available_equipment' => ['parallettes'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'pull_ups' => ['max_strict_reps' => 0, 'fallback_variant' => 'eccentric'],
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'assisted'],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => 8],
                'hollow_hold_seconds' => 10,
                'passive_hang_seconds' => 12,
                'top_support_hold_seconds' => 4,
            ],
        ]));

        $active = $portfolio['active_skill_portfolio'];

        $this->assertNotEmpty($active['foundation_modules']);
        $this->assertLessThanOrEqual(1, $active['optimizer']['high_stress_development_cap']);
        $this->assertLessThanOrEqual($active['optimizer']['high_stress_development_cap'], $this->highDevelopmentCount($active['development_tracks']));
        $this->assertContains('planche', $this->trackIds($active['future_queue']));
    }

    public function test_four_day_intermediate_keeps_development_practice_accessory_and_maintenance_roles(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->strongAthlete([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['handstand', 'l_sit'],
            'weekly_session_goal' => 4,
        ]));

        $active = $portfolio['active_skill_portfolio'];

        $this->assertContains('front_lever', $this->trackIds($active['development_tracks']));
        $this->assertContains('handstand', $this->trackIds($active['technical_practice_tracks']));
        $this->assertContains('l_sit', $this->trackIds($active['accessory_tracks']));
        $this->assertContains('strict_pull_up', $this->trackIds($active['maintenance_tracks']));
        $this->assertArrayHasKey('utility_score', $this->trackById($active['development_tracks'], 'front_lever')['optimizer']);
    }

    public function test_six_day_multi_skill_portfolio_can_select_compatible_second_development_track(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->strongAthlete([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['planche', 'handstand', 'l_sit', 'pistol_squat'],
            'weekly_session_goal' => 6,
            'training_age_months' => 48,
            'readiness_rating' => 5,
            'sleep_quality' => 5,
            'skill_statuses' => [
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
                'planche' => ['status' => 'planche_lean', 'best_hold_seconds' => 18],
                'handstand' => ['status' => 'chest_to_wall_handstand', 'best_hold_seconds' => 45],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 15],
                'pistol_squat' => ['status' => 'split_squat', 'max_reps' => 12],
            ],
        ]));

        $active = $portfolio['active_skill_portfolio'];
        $developmentIds = $this->trackIds($active['development_tracks']);

        $this->assertContains('front_lever', $developmentIds);
        $this->assertGreaterThanOrEqual(2, count($developmentIds));
        $this->assertLessThanOrEqual($active['optimizer']['high_stress_development_cap'], $this->highDevelopmentCount($active['development_tracks']));
        $this->assertContains('handstand', $this->trackIds($active['technical_practice_tracks']));
    }

    public function test_selected_everything_respects_high_stress_caps_and_moves_conflicts_to_not_now(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->strongAthlete([
            'primary_target_skill' => 'planche',
            'secondary_target_skills' => ['handstand_push_up', 'front_lever', 'one_arm_pull_up', 'muscle_up', 'human_flag'],
            'long_term_target_skills' => ['weighted_pull_up', 'weighted_dip'],
            'weekly_session_goal' => 5,
            'training_age_months' => 24,
            'skill_statuses' => [
                'planche' => ['status' => 'planche_lean', 'best_hold_seconds' => 18],
                'handstand_push_up' => ['status' => 'pike_push_up', 'max_reps' => 8],
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
                'one_arm_pull_up' => ['status' => 'archer_pull_up', 'max_reps' => 4],
                'muscle_up' => ['status' => 'chest_to_bar_pull_up', 'max_reps' => 3],
                'human_flag' => ['status' => 'side_plank', 'best_hold_seconds' => 45],
            ],
        ]));

        $active = $portfolio['active_skill_portfolio'];
        $notNowIds = $this->trackIds($portfolio['not_recommended_now']);

        $this->assertLessThanOrEqual($active['optimizer']['high_stress_development_cap'], $this->highDevelopmentCount($active['development_tracks']));
        $this->assertNotContains('handstand_push_up', $this->trackIds($active['development_tracks']));
        $this->assertTrue(
            in_array('handstand_push_up', $notNowIds, true)
            || in_array('one_arm_pull_up', $notNowIds, true)
            || in_array('human_flag', $notNowIds, true),
        );
    }

    public function test_pain_reduced_portfolio_removes_extra_high_stress_lane(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->strongAthlete([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['one_arm_pull_up', 'handstand'],
            'weekly_session_goal' => 5,
            'pain_level' => 4,
            'pain_flags' => [
                'elbow' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
                'one_arm_pull_up' => ['status' => 'archer_pull_up', 'max_reps' => 4],
                'handstand' => ['status' => 'chest_to_wall_handstand', 'best_hold_seconds' => 45],
            ],
        ]));

        $active = $portfolio['active_skill_portfolio'];

        $this->assertSame(1, $active['optimizer']['high_stress_development_cap']);
        $this->assertNotContains('one_arm_pull_up', $this->trackIds($active['development_tracks']));
        $this->assertContains('one_arm_pull_up', $this->trackIds($portfolio['not_recommended_now']));
    }

    public function test_missing_tests_keep_selected_advanced_goal_in_future_queue_with_reasons(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'weekly_session_goal' => 4,
            'available_equipment' => ['pull_up_bar'],
        ]));

        $frontLever = $this->trackById($portfolio['active_skill_portfolio']['future_queue'], 'front_lever')
            ?? $this->trackById($portfolio['not_recommended_now'], 'front_lever');

        $this->assertIsArray($frontLever);
        $this->assertStringContainsString('test', strtolower(implode(' ', [
            ...$frontLever['why_included'],
            ...$frontLever['why_not_higher_priority'],
        ])));
        $this->assertNotEmpty($portfolio['pending_tests']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function strongAthlete(array $overrides): array
    {
        return $this->athleteData(array_replace_recursive([
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes', 'box_bench'],
            'training_age_months' => 24,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 35],
                'pull_ups' => ['max_strict_reps' => 14, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 14, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 20],
                'lower_body' => ['variant' => 'split_squat', 'reps' => 18],
                'hollow_hold_seconds' => 60,
                'passive_hang_seconds' => 75,
                'top_support_hold_seconds' => 50,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_handstand', 'best_hold_seconds' => 20],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 20],
            ],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'clear',
                'pancake_compression' => 'clear',
                'shoulder_extension' => 'clear',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ], $overrides));
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     */
    private function highDevelopmentCount(array $tracks): int
    {
        return count(array_filter(
            $tracks,
            static function (array $track): bool {
                foreach ($track['modules'] ?? [] as $module) {
                    if (in_array($module['intensity_tier'] ?? null, ['high', 'max'], true)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<string>
     */
    private function trackIds(array $tracks): array
    {
        return array_values(array_map(
            static fn (array $track): string => (string) $track['skill_track_id'],
            $tracks,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return array<string, mixed>|null
     */
    private function trackById(array $tracks, string $id): ?array
    {
        foreach ($tracks as $track) {
            if (($track['skill_track_id'] ?? null) === $id) {
                return $track;
            }
        }

        return null;
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
            'preferred_session_minutes' => 60,
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
}
