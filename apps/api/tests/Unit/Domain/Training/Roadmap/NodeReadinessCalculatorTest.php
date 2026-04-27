<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\NodeReadinessCalculator;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class NodeReadinessCalculatorTest extends TestCase
{
    public function test_selected_advanced_goal_becomes_bridge_with_final_target_visible(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'available_equipment' => ['parallettes'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 14],
                'pull_ups' => ['max_strict_reps' => 4, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 4, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 28,
                'top_support_hold_seconds' => 25,
            ],
            'mobility_checks' => [
                'wrist_extension' => 'clear',
            ],
        ]));

        $readiness = NodeReadinessCalculator::forSkill('planche', $input);
        $payload = $readiness->toArray();

        $this->assertSame('planche', $payload['skill_track_id']);
        $this->assertSame('bridge_recommended', $payload['status']);
        $this->assertSame('planche.planche_lean', $payload['current_node']['id']);
        $this->assertSame('planche.frog_stand', $payload['next_node']['id']);
        $this->assertSame('planche.full_planche', $payload['target_node']['id']);
        $this->assertSame('planche.full_planche', $payload['long_term_target_node']['id']);
        $this->assertNotEmpty($payload['warnings']);
        $this->assertGreaterThan(0, $payload['eta_to_target']['max_weeks']);
    }

    public function test_selected_impossible_goal_is_blocked_by_hard_gate(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => [],
            'pain_level' => 7,
            'pain_flags' => [
                'elbow' => ['severity' => 'severe', 'status' => 'acute'],
            ],
        ]));

        $readiness = NodeReadinessCalculator::forSkill('front_lever', $input)->toArray();

        $this->assertSame('blocked_by_hard_gate', $readiness['status']);
        $this->assertStringContainsString('equipment', strtolower(implode(' ', $readiness['blockers'])));
        $this->assertStringContainsString('pain', strtolower(implode(' ', $readiness['blockers'])));
        $this->assertNull($readiness['eta_to_next_node']['min_weeks']);
        $this->assertContains('Hard gates must change before ETA is useful.', $readiness['eta_to_next_node']['modifiers']);
    }

    public function test_edge_eta_widens_from_related_pain(): void
    {
        $base = $this->athleteData([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => ['pull_up_bar'],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 16],
                'hollow_hold_seconds' => 45,
                'passive_hang_seconds' => 60,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
            ],
        ]);

        $normal = NodeReadinessCalculator::forSkill('front_lever', RoadmapInputMapper::fromAthleteData($base))->toArray();
        $pain = NodeReadinessCalculator::forSkill('front_lever', RoadmapInputMapper::fromAthleteData(array_replace_recursive($base, [
            'pain_level' => 4,
            'pain_flags' => [
                'elbow' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ])))->toArray();

        $this->assertGreaterThan($normal['eta_to_next_node']['max_weeks'], $pain['eta_to_next_node']['max_weeks']);
        $this->assertContains('Pain widens the ETA range.', $pain['eta_to_next_node']['modifiers']);
        $this->assertNotSame('ready_for_next_node', $pain['status']);
    }

    public function test_low_confidence_missing_data_blocks_pending_input_and_avoids_precise_eta(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => ['pull_up_bar'],
        ]));

        $readiness = NodeReadinessCalculator::forSkill('front_lever', $input)->toArray();

        $this->assertSame('blocked_pending_input', $readiness['status']);
        $this->assertNull($readiness['eta_to_next_node']['min_weeks']);
        $this->assertNull($readiness['eta_to_next_node']['max_weeks']);
        $this->assertStringContainsString('micro-test', strtolower(implode(' ', $readiness['blockers'])));
    }

    public function test_global_strength_does_not_promote_planche_when_wrist_is_blocked(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'available_equipment' => ['parallettes'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 45],
                'pull_ups' => ['max_strict_reps' => 16, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 18, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 60,
                'top_support_hold_seconds' => 45,
            ],
            'skill_statuses' => [
                'planche' => ['status' => 'planche_lean', 'best_hold_seconds' => 20],
            ],
            'mobility_checks' => [
                'wrist_extension' => 'blocked',
            ],
        ]));

        $readiness = NodeReadinessCalculator::forSkill('planche', $input)->toArray();

        $this->assertSame('blocked_by_hard_gate', $readiness['status']);
        $this->assertStringContainsString('wrist', strtolower(implode(' ', $readiness['blockers'])));
        $this->assertLessThan(80, $readiness['readiness_score']);
    }

    public function test_portfolio_tracks_use_node_readiness_nodes_when_available(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => ['pull_up_bar', 'rings'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 28],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 16],
                'hollow_hold_seconds' => 45,
                'passive_hang_seconds' => 60,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
            ],
        ]));

        $track = $this->trackById($portfolio['active_skill_portfolio']['development_tracks'], 'front_lever');

        $this->assertIsArray($track);
        $this->assertSame('front_lever.tuck_front_lever', $track['current_node']['id']);
        $this->assertSame('front_lever.advanced_tuck_front_lever', $track['next_node']['id']);
        $this->assertSame('front_lever.full_front_lever', $track['target_node']['id']);
        $this->assertSame($track['node_readiness']['status'], $track['mode_detail']['node_status']);
        $this->assertArrayHasKey('readiness_score', $track['node_readiness']);
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
