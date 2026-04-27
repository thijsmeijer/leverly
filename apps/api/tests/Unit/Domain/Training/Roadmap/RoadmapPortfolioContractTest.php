<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapPortfolioContractTest extends TestCase
{
    public function test_empty_portfolio_contract_is_versioned_and_structurally_complete(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::emptyPortfolio();

        $this->assertSame('roadmap.portfolio.v3', $portfolio['version']);
        $this->assertSame('roadmap.v2', $portfolio['source_version']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['development_tracks']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['technical_practice_tracks']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['accessory_tracks']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['maintenance_tracks']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['foundation_tracks']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['future_queue']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['weekly_schedule']['days']);
        $this->assertSame([], $portfolio['pending_tests']);
        $this->assertArrayHasKey('goal_candidates', $portfolio);
        $this->assertArrayHasKey('foundation_layer', $portfolio);
        $this->assertArrayHasKey('stress_ledger', $portfolio['active_skill_portfolio']);
        $this->assertArrayHasKey('time_ledger', $portfolio['active_skill_portfolio']);
        $this->assertArrayHasKey('explanation', $portfolio['active_skill_portfolio']);
    }

    public function test_empty_roadmap_defaults_use_the_portfolio_contract(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::empty();

        $this->assertSame('roadmap.portfolio.v3', $portfolio['version']);
        $this->assertArrayHasKey('active_skill_portfolio', $portfolio);
        $this->assertArrayHasKey('adaptation', $portfolio['active_skill_portfolio']);
        $this->assertSame('prior_based', $portfolio['active_skill_portfolio']['adaptation']['status']);
        $this->assertSame([], $portfolio['active_skill_portfolio']['development_tracks']);
        $this->assertSame([], $portfolio['onboarding_goal_choices']['development']);
    }

    public function test_beginner_bridge_portfolio_keeps_advanced_goal_visible_without_promoting_it(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'available_equipment' => ['rings'],
            'weekly_session_goal' => 3,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'pull_ups' => ['max_strict_reps' => 0, 'fallback_variant' => 'eccentric'],
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'assisted'],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => 10],
                'hollow_hold_seconds' => 10,
                'passive_hang_seconds' => 12,
                'top_support_hold_seconds' => 4,
            ],
        ]));

        $developmentIds = $this->trackIds($portfolio['active_skill_portfolio']['development_tracks']);
        $futureIds = $this->trackIds($portfolio['active_skill_portfolio']['future_queue']);

        $this->assertContains('strict_push_up', $developmentIds);
        $this->assertContains('strict_pull_up', $developmentIds);
        $this->assertContains('planche', $futureIds);
        $this->assertSame('roadmap.portfolio.v3', $portfolio['version']);
        $this->assertNotEmpty($portfolio['goal_candidates']['primary']);
    }

    public function test_intermediate_multi_skill_portfolio_maps_development_practice_accessory_and_maintenance_roles(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['handstand', 'l_sit'],
            'long_term_target_skills' => ['planche'],
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes', 'box_bench'],
            'weekly_session_goal' => 4,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 28],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 18],
                'lower_body' => ['variant' => 'split_squat', 'reps' => 14],
                'hollow_hold_seconds' => 45,
                'passive_hang_seconds' => 60,
                'top_support_hold_seconds' => 40,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'wall_handstand', 'best_hold_seconds' => 35],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 12],
            ],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'clear',
                'pancake_compression' => 'clear',
                'shoulder_extension' => 'clear',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ]));

        $this->assertContains('front_lever', $this->trackIds($portfolio['active_skill_portfolio']['development_tracks']));
        $this->assertContains('handstand', $this->trackIds($portfolio['active_skill_portfolio']['technical_practice_tracks']));
        $this->assertContains('l_sit', $this->trackIds($portfolio['active_skill_portfolio']['accessory_tracks']));
        $this->assertContains('strict_pull_up', $this->trackIds($portfolio['active_skill_portfolio']['maintenance_tracks']));
        $this->assertContains('planche', $this->trackIds($portfolio['long_term_aspirations']));

        $track = $portfolio['active_skill_portfolio']['development_tracks'][0];

        $this->assertArrayHasKey('current_node', $track);
        $this->assertArrayHasKey('next_node', $track);
        $this->assertArrayHasKey('target_node', $track);
        $this->assertArrayHasKey('eta_to_next_node', $track);
        $this->assertArrayHasKey('modules', $track);
        $this->assertArrayHasKey('why_included', $track);
        $this->assertArrayHasKey('why_not_higher_priority', $track);
    }

    public function test_blocked_pain_or_equipment_cases_are_separated_from_not_recommended_now(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['one_arm_pull_up'],
            'long_term_target_skills' => ['planche'],
            'available_equipment' => [],
            'weekly_session_goal' => 3,
            'pain_level' => 7,
            'pain_flags' => [
                'elbow' => ['severity' => 'severe', 'status' => 'acute'],
                'wrist' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ]));

        $blockedIds = $this->trackIds($portfolio['blocked']);
        $frontLever = $this->trackById($portfolio['blocked'], 'front_lever');

        $this->assertContains('front_lever', $blockedIds);
        $this->assertNotContains('front_lever', $this->trackIds($portfolio['not_recommended_now']));
        $this->assertIsArray($frontLever);
        $this->assertStringContainsString('Pain', implode(' ', $frontLever['why_included']));
    }

    public function test_high_frequency_portfolio_has_six_day_contract_shape(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'muscle_up',
            'secondary_target_skills' => ['front_lever', 'handstand', 'l_sit'],
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes', 'box_bench'],
            'weekly_session_goal' => 6,
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
        ]));

        $this->assertSame(6, $portfolio['active_skill_portfolio']['time_ledger']['max_sessions_per_week']);
        $this->assertCount(6, $portfolio['active_skill_portfolio']['weekly_schedule']['days']);
        $this->assertContains('pull_strength', array_column($portfolio['active_skill_portfolio']['weekly_schedule']['days'], 'day_type'));
        $this->assertContains('push_strength', array_column($portfolio['active_skill_portfolio']['weekly_schedule']['days'], 'day_type'));
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
}
