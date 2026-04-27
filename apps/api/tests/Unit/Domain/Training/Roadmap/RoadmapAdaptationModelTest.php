<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapAdaptationEvidenceProvider;
use App\Domain\Training\Roadmap\RoadmapAdaptationInput;
use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapAdaptationModelTest extends TestCase
{
    public function test_no_log_portfolio_marks_eta_as_prior_based(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolio($this->input());
        $track = $portfolio['active_skill_portfolio']['development_tracks'][0];

        $this->assertSame('prior_based', $portfolio['active_skill_portfolio']['adaptation']['status']);
        $this->assertSame('prior', $portfolio['active_skill_portfolio']['adaptation']['eta_basis']);
        $this->assertSame(0, $portfolio['active_skill_portfolio']['adaptation']['evidence_weeks']);
        $this->assertSame(0.0, $portfolio['active_skill_portfolio']['adaptation']['blend_weight']);
        $this->assertSame('prior', $track['adaptation']['eta_basis']);
        $this->assertContains('No logged training evidence yet; ETA uses baseline and graph priors.', $track['adaptation']['warnings']);
    }

    public function test_synthetic_log_evidence_uses_expected_blend_weights_by_week_count(): void
    {
        foreach ([2 => 0.25, 4 => 0.4, 8 => 0.65, 12 => 0.8] as $weeks => $expectedWeight) {
            $portfolio = CalisthenicsRoadmapSuggester::portfolio(
                $this->input(),
                includeIntermediate: false,
                adaptationProvider: new ArrayRoadmapAdaptationEvidenceProvider($this->evidence($weeks, progressDelta: 0.1)),
            );

            $this->assertSame($weeks, $portfolio['active_skill_portfolio']['adaptation']['evidence_weeks']);
            $this->assertSame($expectedWeight, $portfolio['active_skill_portfolio']['adaptation']['blend_weight']);
            $this->assertSame('blended', $portfolio['active_skill_portfolio']['adaptation']['eta_basis']);
        }
    }

    public function test_good_completed_evidence_tightens_eta_without_overriding_the_prior(): void
    {
        $prior = CalisthenicsRoadmapSuggester::portfolio($this->input());
        $adapted = CalisthenicsRoadmapSuggester::portfolio(
            $this->input(),
            includeIntermediate: false,
            adaptationProvider: new ArrayRoadmapAdaptationEvidenceProvider($this->evidence(8, progressDelta: 0.35)),
        );

        $priorTrack = $prior['active_skill_portfolio']['development_tracks'][0];
        $adaptedTrack = $adapted['active_skill_portfolio']['development_tracks'][0];

        $this->assertSame('tightened', $adaptedTrack['adaptation']['status']);
        $this->assertLessThan($priorTrack['eta_to_next_node']['max_weeks'], $adaptedTrack['eta_to_next_node']['max_weeks']);
        $this->assertContains('Observed progress tightened the ETA range.', $adaptedTrack['eta_to_next_node']['modifiers']);
    }

    public function test_missed_exposures_widen_eta_and_create_warning(): void
    {
        $prior = CalisthenicsRoadmapSuggester::portfolio($this->input());
        $adapted = CalisthenicsRoadmapSuggester::portfolio(
            $this->input(),
            includeIntermediate: false,
            adaptationProvider: new ArrayRoadmapAdaptationEvidenceProvider(
                $this->evidence(4, completedExposures: 1, plannedExposures: 3, progressDelta: -0.05),
            ),
        );

        $priorTrack = $prior['active_skill_portfolio']['development_tracks'][0];
        $adaptedTrack = $adapted['active_skill_portfolio']['development_tracks'][0];

        $this->assertSame('widened', $adaptedTrack['adaptation']['status']);
        $this->assertGreaterThan($priorTrack['eta_to_next_node']['max_weeks'], $adaptedTrack['eta_to_next_node']['max_weeks']);
        $this->assertContains('Missed exposures widen the ETA until weekly consistency improves.', $adaptedTrack['adaptation']['warnings']);
    }

    public function test_pain_driven_evidence_downgrades_progression(): void
    {
        $adapted = CalisthenicsRoadmapSuggester::portfolio(
            $this->input(),
            includeIntermediate: false,
            adaptationProvider: new ArrayRoadmapAdaptationEvidenceProvider($this->evidence(4, painLevel: 5)),
        );

        $track = $adapted['active_skill_portfolio']['development_tracks'][0];

        $this->assertSame('downgrade', $track['adaptation']['status']);
        $this->assertSame('hold_or_deload', $track['adaptation']['next_action']);
        $this->assertContains('Pain reached 4/10 or higher; hold progression and use the fallback/regression.', $track['adaptation']['warnings']);
    }

    public function test_poor_form_requests_retest_before_progression(): void
    {
        $adapted = CalisthenicsRoadmapSuggester::portfolio(
            $this->input(),
            includeIntermediate: false,
            adaptationProvider: new ArrayRoadmapAdaptationEvidenceProvider($this->evidence(4, formScore: 2.0)),
        );

        $track = $adapted['active_skill_portfolio']['development_tracks'][0];

        $this->assertSame('retest_requested', $track['adaptation']['status']);
        $this->assertSame('retest_or_technique_focus', $track['adaptation']['next_action']);
        $this->assertContains('Form quality is too low for progression; request a retest or technique block.', $track['adaptation']['warnings']);
    }

    private function input(): RoadmapInput
    {
        return RoadmapInputMapper::fromAthleteData([
            'age_years' => 29,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'maintaining',
            'training_age_months' => 24,
            'weekly_session_goal' => 4,
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes'],
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
            'skill_statuses' => [],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'clear',
                'pancake_compression' => 'clear',
                'shoulder_extension' => 'clear',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
            'weighted_baselines' => ['experience' => 'none', 'unit' => 'kg', 'movements' => []],
            'goal_modules' => [
                'pull_skill' => [
                    'highest_progression' => 'tuck_front_lever',
                    'metric_type' => 'hold_seconds',
                    'reps' => null,
                    'hold_seconds' => 12,
                    'load_value' => null,
                    'load_unit' => 'kg',
                    'quality' => 'clean',
                    'notes' => null,
                ],
            ],
            'pain_level' => null,
            'pain_flags' => [],
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => [],
            'long_term_target_skills' => [],
        ]);
    }

    private function evidence(
        int $weeks,
        int $completedExposures = 2,
        int $plannedExposures = 2,
        float $progressDelta = 0.0,
        int $painLevel = 1,
        float $formScore = 4.0,
    ): RoadmapAdaptationInput {
        $sessions = [];

        for ($week = 1; $week <= $weeks; $week++) {
            $sessions[] = [
                'week_number' => $week,
                'pain_level' => $painLevel,
                'readiness_score' => 0.82,
                'modules' => [
                    [
                        'module_id' => 'front_lever.development',
                        'skill_track_id' => 'front_lever',
                        'planned_exposures' => $plannedExposures,
                        'completed_exposures' => $completedExposures,
                        'progress_delta' => $progressDelta,
                        'form_score' => $formScore,
                        'rir' => 2,
                        'rpe' => 8,
                        'pain_level' => $painLevel,
                    ],
                ],
            ];
        }

        return RoadmapAdaptationInput::fromSessions($sessions);
    }
}

final readonly class ArrayRoadmapAdaptationEvidenceProvider implements RoadmapAdaptationEvidenceProvider
{
    public function __construct(private RoadmapAdaptationInput $input) {}

    public function evidenceFor(RoadmapInput $input, array $portfolio): RoadmapAdaptationInput
    {
        return $this->input;
    }
}
