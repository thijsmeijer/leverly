<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\DomainScore;
use App\Domain\Training\Roadmap\DomainScoreCalculator;
use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use PHPUnit\Framework\TestCase;

class DomainScoreCalculatorTest extends TestCase
{
    private const array INTERNAL_DOMAINS = [
        'bent_arm_push',
        'vertical_push',
        'straight_arm_push',
        'bent_arm_pull',
        'explosive_pull',
        'horizontal_pull',
        'straight_arm_pull',
        'grip_hang',
        'scapular_control',
        'trunk_rigidity',
        'compression',
        'inversion_balance',
        'lower_body_squat',
        'unilateral_leg',
        'posterior_chain',
        'wrist_loaded_extension',
        'elbow_pull_tendon',
        'elbow_push_tendon',
        'shoulder_flexion',
        'shoulder_extension',
        'shoulder_straight_arm',
        'ankle_dorsiflexion',
        'recovery_capacity',
    ];

    private const array DISPLAY_DOMAINS = [
        'push_strength',
        'pull_strength',
        'straight_arm_strength',
        'core_bodyline',
        'balance_inversion',
        'lower_body',
        'tissue_readiness',
    ];

    public function test_it_returns_granular_internal_domains_display_domains_and_compatibility_aliases(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 12],
                'pull_ups' => ['max_strict_reps' => 6, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 5, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'bodyweight_row', 'max_reps' => 12],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => 20, 'load_unit' => 'kg', 'load_value' => null],
                'hollow_hold_seconds' => 30,
                'passive_hang_seconds' => 45,
                'top_support_hold_seconds' => 30,
            ],
        ]));

        foreach (self::INTERNAL_DOMAINS as $domain) {
            $this->assertArrayHasKey($domain, $scores);
            $this->assertSame('internal', $scores[$domain]->kind);
            $this->assertNotEmpty($scores[$domain]->displayDomain);
        }

        foreach (self::DISPLAY_DOMAINS as $domain) {
            $this->assertArrayHasKey($domain, $scores);
            $this->assertSame('display', $scores[$domain]->kind);
            $this->assertNotEmpty($scores[$domain]->weakLinks);
        }

        foreach (['vertical_push', 'vertical_pull', 'horizontal_pull_straight_arm_pull', 'lower_body_strength', 'tissue_tolerance'] as $legacyKey) {
            $this->assertArrayHasKey($legacyKey, $scores);
        }

        $array = $scores['wrist_loaded_extension']->toArray();

        $this->assertSame('internal', $array['kind']);
        $this->assertSame('tissue_readiness', $array['display_domain']);
        $this->assertArrayHasKey('weak_links', $array);
    }

    public function test_high_leverage_evidence_can_score_high_with_visible_uncertainty(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_bodyweight_value' => 82,
            'height_value' => 183,
            'training_age_months' => 18,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 45,
            ],
            'weighted_baselines' => [
                'unit' => 'kg',
                'movements' => [
                    ['movement' => 'weighted_pull_up', 'external_load_value' => 32, 'reps' => 4, 'rir' => 1],
                ],
            ],
        ]));

        $verticalPull = $scores['vertical_pull'];

        $this->assertGreaterThan(75, $verticalPull->score);
        $this->assertGreaterThan(0.35, $verticalPull->uncertainty);
        $this->assertLessThan(0.65, $verticalPull->confidence);
        $this->assertContains('Weighted pull-up: 32kg for 4 reps.', $verticalPull->contributingInputs);
        $this->assertContains('One-arm pull-up progression evidence.', $verticalPull->missingInputs);
        $this->assertContains('Body mass: 82kg.', $verticalPull->modifiers);
        $this->assertSame('Strong signal, but missing inputs keep this domain uncertain.', $verticalPull->bottleneck);
    }

    public function test_low_score_can_have_high_confidence_when_weakness_is_observed(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'none'],
                'top_support_hold_seconds' => 0,
            ],
        ]));

        $verticalPush = $scores['vertical_push'];

        $this->assertLessThan(10, $verticalPush->score);
        $this->assertGreaterThan(0.65, $verticalPush->confidence);
        $this->assertLessThan(0.35, $verticalPush->uncertainty);
        $this->assertContains('Push-ups: 0 reps.', $verticalPush->contributingInputs);
        $this->assertContains('Dips: 0 reps.', $verticalPush->contributingInputs);
        $this->assertSame('This is a clear bottleneck from observed tests.', $verticalPush->bottleneck);
    }

    public function test_pain_limits_tissue_tolerance_before_overload_decisions(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'pain_level' => 6,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 50,
                'top_support_hold_seconds' => 40,
            ],
        ]));

        $tissue = $scores['tissue_tolerance'];

        $this->assertLessThanOrEqual(45, $tissue->score);
        $this->assertGreaterThan(0.6, $tissue->confidence);
        $this->assertContains('Pain level: 6/10.', $tissue->contributingInputs);
        $this->assertSame('Pain is the limiting tissue-tolerance signal; hold progression until it settles.', $tissue->bottleneck);
    }

    public function test_planche_wrist_pain_limits_wrist_loaded_extension_without_erasing_other_domains(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 20],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 35,
                'top_support_hold_seconds' => 35,
            ],
            'pain_flags' => [
                'wrist' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ]));

        $wrist = $scores['wrist_loaded_extension'];

        $this->assertLessThanOrEqual(45, $wrist->score);
        $this->assertGreaterThan(0.65, $wrist->confidence);
        $this->assertContains('Recent or significant wrist pain limits loaded wrist extension.', $wrist->contributingInputs);
        $this->assertContains('wrist_loaded_extension', $scores['tissue_readiness']->weakLinks);
        $this->assertGreaterThan(60, $scores['bent_arm_push']->score);
    }

    public function test_front_lever_elbow_pain_limits_elbow_pull_tendon_as_a_weak_link(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 14],
                'passive_hang_seconds' => 50,
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'advanced_tuck_front_lever', 'best_hold_seconds' => 8],
            ],
            'pain_flags' => [
                'elbow' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ]));

        $elbowPull = $scores['elbow_pull_tendon'];

        $this->assertLessThanOrEqual(45, $elbowPull->score);
        $this->assertContains('Recent or significant elbow pain limits elbow pull tendon readiness.', $elbowPull->contributingInputs);
        $this->assertContains('elbow_pull_tendon', $scores['straight_arm_strength']->weakLinks);
    }

    public function test_shoulder_extension_limits_dip_and_back_lever_tissue_readiness(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'dips' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'top_support_hold_seconds' => 40,
            ],
            'skill_statuses' => [
                'back_lever' => ['status' => 'tuck_back_lever', 'best_hold_seconds' => 8],
            ],
            'pain_flags' => [
                'shoulder' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
            'mobility_checks' => [
                'shoulder_extension' => 'painful',
            ],
        ]));

        $shoulderExtension = $scores['shoulder_extension'];

        $this->assertLessThanOrEqual(30, $shoulderExtension->score);
        $this->assertContains('Shoulder extension is painful.', $shoulderExtension->contributingInputs);
        $this->assertContains('shoulder_extension', $scores['tissue_readiness']->weakLinks);
    }

    public function test_hspu_shoulder_flexion_and_pistol_ankle_limits_are_direction_specific(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => 24, 'load_unit' => 'kg', 'load_value' => null],
            ],
            'mobility_checks' => [
                'shoulder_flexion' => 'blocked',
                'ankle_dorsiflexion' => 'painful',
            ],
        ]));

        $this->assertLessThanOrEqual(20, $scores['shoulder_flexion']->score);
        $this->assertLessThanOrEqual(30, $scores['ankle_dorsiflexion']->score);
        $this->assertContains('shoulder_flexion', $scores['balance_inversion']->weakLinks);
        $this->assertContains('ankle_dorsiflexion', $scores['lower_body']->weakLinks);
        $this->assertGreaterThan(45, $scores['bent_arm_push']->score);
    }

    public function test_missing_data_returns_all_domains_with_high_uncertainty(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([]));

        foreach ([...self::INTERNAL_DOMAINS, ...self::DISPLAY_DOMAINS] as $domain) {
            $this->assertArrayHasKey($domain, $scores);
        }

        $this->assertArrayHasKey('tissue_tolerance', $scores);
        $this->assertContainsOnlyInstancesOf(DomainScore::class, $scores);

        foreach ($scores as $score) {
            $this->assertGreaterThan(0.65, $score->uncertainty);
            $this->assertNotEmpty($score->missingInputs);
            $this->assertNotEmpty($score->weakLinks);
            $this->assertSame('Missing inputs make this domain uncertain.', $score->bottleneck);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(array $overrides): RoadmapInput
    {
        return RoadmapInputMapper::fromAthleteData(array_replace_recursive([
            'age_years' => 31,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'maintaining',
            'training_age_months' => 12,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => null],
                'pull_ups' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'squat' => ['barbell_load_value' => null, 'barbell_reps' => null],
                'rows' => ['variant' => 'bodyweight_row', 'max_reps' => null],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => null, 'load_unit' => 'kg', 'load_value' => null],
                'hollow_hold_seconds' => null,
                'passive_hang_seconds' => null,
                'top_support_hold_seconds' => null,
            ],
            'skill_statuses' => [],
            'weighted_baselines' => ['experience' => 'none', 'unit' => 'kg', 'movements' => []],
            'goal_modules' => [],
            'pain_level' => null,
        ], $overrides));
    }
}
