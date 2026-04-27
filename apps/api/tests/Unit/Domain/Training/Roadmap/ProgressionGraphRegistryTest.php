<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\ProgressionGraphRegistry;
use PHPUnit\Framework\TestCase;

class ProgressionGraphRegistryTest extends TestCase
{
    public function test_it_defines_the_required_progression_graph_families(): void
    {
        $requiredFamilies = [
            'push_up',
            'pull_up',
            'dip',
            'row',
            'bodyline',
            'support',
            'lower_body',
            'compression',
            'handstand',
            'hspu',
            'muscle_up',
            'front_lever',
            'back_lever',
            'planche',
            'one_arm_pull_up',
            'pistol_squat',
            'human_flag',
        ];

        $this->assertSame($requiredFamilies, ProgressionGraphRegistry::families());

        foreach ($requiredFamilies as $family) {
            $graph = ProgressionGraphRegistry::require($family);

            $this->assertSame($family, $graph->family);
            $this->assertNotEmpty($graph->label);
            $this->assertNotEmpty($graph->nodes());
        }
    }

    public function test_every_graph_node_has_complete_stable_metadata(): void
    {
        foreach (ProgressionGraphRegistry::all() as $graph) {
            $previousOrder = -1;
            $seenSlugs = [];

            foreach ($graph->nodes() as $node) {
                $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $node->slug);
                $this->assertNotContains($node->slug, $seenSlugs);
                $this->assertNotSame($node->slug, $node->label);
                $this->assertStringNotContainsString('_', $node->label);
                $this->assertGreaterThan($previousOrder, $node->order);
                $this->assertSame($graph->family, $node->family);
                $this->assertContains($node->metricType, ProgressionGraphRegistry::metricTypes());
                $this->assertGreaterThan(0, $node->minEdgeWeeks);
                $this->assertGreaterThanOrEqual($node->minEdgeWeeks, $node->maxEdgeWeeks);
                $this->assertNotEmpty($node->unlock);

                $this->assertSame([
                    'min_weeks' => $node->minEdgeWeeks,
                    'max_weeks' => $node->maxEdgeWeeks,
                ], $node->edgeTimeBand());

                $array = $node->toArray();

                $this->assertSame($node->slug, $array['slug']);
                $this->assertSame($node->label, $array['label']);
                $this->assertSame($node->order, $array['order']);
                $this->assertSame($node->family, $array['family']);
                $this->assertSame($node->metricType, $array['metric_type']);
                $this->assertSame($node->edgeTimeBand(), $array['edge_time_band']);
                $this->assertSame($node->unlock, $array['unlock']);

                $previousOrder = $node->order;
                $seenSlugs[] = $node->slug;
            }
        }
    }

    public function test_pull_graph_distinguishes_vertical_pull_and_one_arm_branches(): void
    {
        $this->assertGraphContainsSequence('pull_up', [
            'no_vertical_pull',
            'passive_hang',
            'active_hang',
            'scapular_pull',
            'eccentric_pull_up',
            'assisted_pull_up',
            'first_pull_up',
            'multiple_pull_ups',
            'three_by_eight_pull_ups',
            'explosive_pull_up',
            'weighted_pull_up',
            'archer_pull_up',
            'assisted_one_arm_pull_up',
            'one_arm_pull_up_negative',
            'one_arm_pull_up',
        ]);

        $this->assertSame(
            'Multiple pull-ups',
            ProgressionGraphRegistry::nextNode('pull_up', 'first_pull_up')?->label,
        );
    }

    public function test_dip_graph_distinguishes_support_depth_rings_and_weighted_capacity(): void
    {
        $this->assertGraphContainsSequence('dip', [
            'no_support',
            'top_support',
            'assisted_dip',
            'first_dip',
            'multiple_dips',
            'deep_dip_capacity',
            'ring_support',
            'ring_dip',
            'weighted_dip',
        ]);
    }

    public function test_handstand_graph_contains_balance_and_hspu_related_branches(): void
    {
        $this->assertGraphContainsSequence('handstand', [
            'wall_plank',
            'chest_to_wall_handstand',
            'wall_handstand_shoulder_taps',
            'freestanding_kick_up',
            'freestanding_handstand',
            'pike_push_up',
            'wall_hspu_negative',
            'partial_wall_hspu',
            'full_wall_hspu',
            'deep_handstand_push_up',
            'freestanding_handstand_push_up',
        ]);

        $node = ProgressionGraphRegistry::node('handstand', 'freestanding_kick_up');

        $this->assertSame('Freestanding kick-up', $node?->label);
        $this->assertSame('hold_seconds', $node?->metricType);
    }

    public function test_target_skills_map_to_their_owning_graph_family(): void
    {
        $expected = [
            'strict_push_up' => 'push_up',
            'one_arm_push_up' => 'push_up',
            'strict_pull_up' => 'pull_up',
            'weighted_pull_up' => 'pull_up',
            'strict_dip' => 'dip',
            'ring_dip' => 'dip',
            'weighted_dip' => 'dip',
            'muscle_up' => 'muscle_up',
            'weighted_muscle_up' => 'muscle_up',
            'l_sit' => 'compression',
            'v_sit' => 'compression',
            'handstand' => 'handstand',
            'handstand_push_up' => 'hspu',
            'press_to_handstand' => 'handstand',
            'front_lever' => 'front_lever',
            'back_lever' => 'back_lever',
            'planche' => 'planche',
            'pistol_squat' => 'pistol_squat',
            'nordic_curl' => 'lower_body',
            'one_arm_pull_up' => 'one_arm_pull_up',
            'human_flag' => 'human_flag',
        ];

        foreach ($expected as $skill => $family) {
            $this->assertSame($family, ProgressionGraphRegistry::targetFamily($skill));
            $this->assertSame($family, ProgressionGraphRegistry::forTargetSkill($skill)?->family);
        }

        $this->assertNull(ProgressionGraphRegistry::targetFamily('unknown_skill'));
        $this->assertNull(ProgressionGraphRegistry::forTargetSkill('unknown_skill'));
    }

    /**
     * @param  list<string>  $expectedSequence
     */
    private function assertGraphContainsSequence(string $family, array $expectedSequence): void
    {
        $actual = ProgressionGraphRegistry::require($family)->nodeSlugs();
        $position = 0;

        foreach ($expectedSequence as $expectedSlug) {
            $foundAt = array_search($expectedSlug, array_slice($actual, $position, null, true), true);

            $this->assertNotFalse($foundAt, sprintf('Expected graph [%s] to contain node [%s] in order.', $family, $expectedSlug));

            $position = $foundAt + 1;
        }
    }
}
