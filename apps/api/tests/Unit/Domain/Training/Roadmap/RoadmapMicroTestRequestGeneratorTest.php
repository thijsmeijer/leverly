<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapMicroTestRequestGenerator;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapMicroTestRequestGeneratorTest extends TestCase
{
    public function test_selected_planche_requests_focused_missing_micro_tests(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 24],
                'pull_ups' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 35,
            ],
            'mobility_checks' => [
                'shoulder_flexion' => 'clear',
                'shoulder_extension' => 'clear',
            ],
        ]));

        $requests = RoadmapMicroTestRequestGenerator::fromInput($input);
        $keys = $this->requestKeys($requests);

        $this->assertSame([
            'planche.planche_lean_hold',
            'planche.wrist_extension_comfort',
            'planche.frog_stand_attempt',
        ], $keys);

        $lean = $this->requestByKey($requests, 'planche.planche_lean_hold');

        $this->assertSame('planche', $lean['target_skill']);
        $this->assertSame('Planche', $lean['target_label']);
        $this->assertSame('planche.planche_lean', $lean['related_node']['id']);
        $this->assertSame('hold_seconds', $lean['measurement_type']);
        $this->assertSame(['type' => 'number', 'unit' => 'seconds', 'min' => 0, 'max' => 60], $lean['response_shape']);
        $this->assertNotSame('', $lean['prompt']);
        $this->assertNotSame('', $lean['why_it_matters']);
        $this->assertStringContainsString('bridge', $lean['skip_behavior']);
        $this->assertFalse($lean['blocking']);
        $this->assertSame('requested', $lean['state']);
        $this->assertLessThan(0, $lean['confidence_impact']['missing_delta']);
        $this->assertGreaterThan(0, $lean['confidence_impact']['completed_delta']);
    }

    public function test_not_tested_micro_tests_lower_confidence_without_counting_as_zero(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'skill_statuses' => [
                'front_lever' => ['status' => 'not_tested'],
            ],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => null],
                'hollow_hold_seconds' => 40,
                'passive_hang_seconds' => 45,
            ],
        ]));

        $requests = RoadmapMicroTestRequestGenerator::fromInput($input);
        $tuckHold = $this->requestByKey($requests, 'front_lever.tuck_hold');

        $this->assertSame('not_tested', $tuckHold['state']);
        $this->assertFalse($tuckHold['blocking']);
        $this->assertSame('bridge_recommendation', $tuckHold['not_tested_behavior']);
        $this->assertLessThan(0, $tuckHold['confidence_impact']['missing_delta']);
        $this->assertStringContainsString('not counted as zero', $tuckHold['skip_behavior']);
    }

    public function test_completed_micro_test_signals_remove_matching_requests(): void
    {
        $missingInput = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
        ]));
        $completedInput = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'skill_statuses' => [
                'planche' => ['status' => 'tuck_planche', 'best_hold_seconds' => 8],
            ],
            'mobility_checks' => [
                'wrist_extension' => 'clear',
            ],
        ]));

        $this->assertContains('planche.planche_lean_hold', $this->requestKeys(RoadmapMicroTestRequestGenerator::fromInput($missingInput)));
        $this->assertSame([], RoadmapMicroTestRequestGenerator::fromInput($completedInput));
    }

    public function test_portfolio_output_contains_only_relevant_target_micro_tests(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'muscle_up',
            'secondary_target_skills' => ['front_lever'],
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 30],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => null],
                'hollow_hold_seconds' => 45,
                'passive_hang_seconds' => 60,
                'top_support_hold_seconds' => 35,
            ],
        ]));

        $keys = $this->requestKeys($portfolio['pending_tests']);

        $this->assertContains('muscle_up.chest_to_bar_pull_up', $keys);
        $this->assertContains('muscle_up.transition_drill', $keys);
        $this->assertContains('front_lever.horizontal_row_capacity', $keys);
        $this->assertNotContains('planche.planche_lean_hold', $keys);
        $this->assertSame('muscle_up', $this->requestByKey($portfolio['pending_tests'], 'muscle_up.chest_to_bar_pull_up')['target_skill']);
    }

    /**
     * @param  list<array<string, mixed>>  $requests
     * @return list<string>
     */
    private function requestKeys(array $requests): array
    {
        return array_values(array_map(
            static fn (array $request): string => (string) $request['key'],
            $requests,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $requests
     * @return array<string, mixed>
     */
    private function requestByKey(array $requests, string $key): array
    {
        foreach ($requests as $request) {
            if (($request['key'] ?? null) === $key) {
                return $request;
            }
        }

        $this->fail("Expected micro-test request [{$key}] was not generated.");
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
