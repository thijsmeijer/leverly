<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

use InvalidArgumentException;

final class ProgressionGraphRegistry
{
    public const array METRIC_TYPES = [
        'reps',
        'hold_seconds',
        'load',
        'quality',
    ];

    private const array TARGET_SKILL_FAMILIES = [
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

    /**
     * This registry is the current boundary for graph consumers. Keep callers on
     * these methods so later seeded progression storage can replace the arrays.
     *
     * @return array<string, ProgressionGraph>
     */
    public static function all(): array
    {
        /** @var array<string, ProgressionGraph>|null $graphs */
        static $graphs = null;

        if ($graphs !== null) {
            return $graphs;
        }

        $graphs = [];

        foreach (self::definitions() as $family => $definition) {
            $graphs[$family] = self::graph($family, $definition['label'], $definition['nodes']);
        }

        return $graphs;
    }

    /**
     * @return list<string>
     */
    public static function families(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return list<string>
     */
    public static function metricTypes(): array
    {
        return self::METRIC_TYPES;
    }

    public static function get(string $family): ?ProgressionGraph
    {
        return self::all()[$family] ?? null;
    }

    public static function require(string $family): ProgressionGraph
    {
        $graph = self::get($family);

        if ($graph === null) {
            throw new InvalidArgumentException(sprintf('Unknown progression graph family [%s].', $family));
        }

        return $graph;
    }

    public static function node(string $family, string $slug): ?ProgressionGraphNode
    {
        return self::get($family)?->node($slug);
    }

    public static function nextNode(string $family, string $slug): ?ProgressionGraphNode
    {
        return self::get($family)?->nextNode($slug);
    }

    public static function targetFamily(string $targetSkill): ?string
    {
        return self::TARGET_SKILL_FAMILIES[$targetSkill] ?? null;
    }

    public static function forTargetSkill(string $targetSkill): ?ProgressionGraph
    {
        $family = self::targetFamily($targetSkill);

        if ($family === null) {
            return null;
        }

        return self::get($family);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: int, 4: int, 5: string}>  $nodes
     */
    private static function graph(string $family, string $label, array $nodes): ProgressionGraph
    {
        $order = 10;
        $graphNodes = [];

        foreach ($nodes as [$slug, $nodeLabel, $metricType, $minWeeks, $maxWeeks, $unlock]) {
            $graphNodes[] = new ProgressionGraphNode(
                slug: $slug,
                label: $nodeLabel,
                order: $order,
                family: $family,
                metricType: $metricType,
                minEdgeWeeks: $minWeeks,
                maxEdgeWeeks: $maxWeeks,
                unlock: $unlock,
            );

            $order += 10;
        }

        return new ProgressionGraph($family, $label, $graphNodes);
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     nodes: list<array{0: string, 1: string, 2: string, 3: int, 4: int, 5: string}>
     * }>
     */
    private static function definitions(): array
    {
        return [
            'push_up' => [
                'label' => 'Push-up',
                'nodes' => [
                    ['no_push_up', 'No push-up yet', 'quality', 1, 3, 'Cannot complete a controlled floor push-up, so the first unlock is a stable incline or reduced-load press.'],
                    ['incline_push_up', 'Incline push-up', 'reps', 2, 5, 'Can press from a raised surface with full-body tension and no shoulder pain.'],
                    ['knee_push_up', 'Knee push-up', 'reps', 2, 5, 'Can control a shortened-lever push-up while keeping ribs and hips stacked.'],
                    ['first_push_up', 'First push-up', 'reps', 2, 6, 'Can complete one controlled floor rep through a useful range of motion.'],
                    ['multiple_push_ups', 'Multiple push-ups', 'reps', 3, 8, 'Can repeat floor reps without losing bodyline or shoulder position.'],
                    ['three_by_ten_push_ups', '3x10 push-ups', 'reps', 4, 10, 'Can accumulate enough clean volume for harder pressing variations.'],
                    ['diamond_push_up', 'Diamond push-up', 'reps', 3, 8, 'Can shift more work toward triceps while preserving shoulder comfort.'],
                    ['pseudo_planche_push_up', 'Pseudo planche push-up', 'reps', 4, 12, 'Can tolerate forward shoulder loading with straight-body control.'],
                    ['archer_push_up', 'Archer push-up', 'reps', 5, 12, 'Can bias one arm while the other assists through a controlled side-to-side press.'],
                    ['one_arm_push_up_negative', 'One-arm push-up negative', 'reps', 6, 16, 'Can lower on one arm with rotation, trunk, and shoulder control.'],
                    ['one_arm_push_up', 'One-arm push-up', 'reps', 8, 20, 'Can press back to the top on one arm with a repeatable setup.'],
                ],
            ],
            'pull_up' => [
                'label' => 'Pull-up',
                'nodes' => [
                    ['no_vertical_pull', 'No vertical pull yet', 'quality', 1, 3, 'Cannot yet hang or pull vertically, so the first unlock is supported shoulder control.'],
                    ['passive_hang', 'Passive hang', 'hold_seconds', 2, 5, 'Can hang from a bar with relaxed shoulders and no grip or joint limitation.'],
                    ['active_hang', 'Active hang', 'hold_seconds', 2, 5, 'Can depress and set the shoulders while hanging.'],
                    ['scapular_pull', 'Scapular pull', 'reps', 2, 5, 'Can repeat shoulder-blade pulls without bending the elbows.'],
                    ['eccentric_pull_up', 'Eccentric pull-up', 'reps', 3, 7, 'Can lower from the top position under control.'],
                    ['assisted_pull_up', 'Assisted pull-up', 'reps', 3, 8, 'Can complete full-range reps with band, foot, or machine assistance.'],
                    ['first_pull_up', 'First pull-up', 'reps', 4, 10, 'Can complete one unassisted rep from dead hang to chin over bar.'],
                    ['multiple_pull_ups', 'Multiple pull-ups', 'reps', 4, 10, 'Can repeat unassisted reps without kipping or losing shoulder control.'],
                    ['three_by_eight_pull_ups', '3x8 pull-ups', 'reps', 6, 14, 'Can handle enough vertical-pull volume for power, weight, or one-arm preparation.'],
                    ['explosive_pull_up', 'Explosive pull-up', 'reps', 5, 12, 'Can pull high enough to train bar speed and transition height.'],
                    ['weighted_pull_up', 'Weighted pull-up', 'load', 6, 18, 'Can add external load while keeping range and elbow comfort.'],
                    ['archer_pull_up', 'Archer pull-up', 'reps', 6, 16, 'Can bias one side while the opposite arm assists.'],
                    ['assisted_one_arm_pull_up', 'Assisted one-arm pull-up', 'reps', 8, 20, 'Can train one-arm pulling with controlled assistance and no elbow flare-up.'],
                    ['one_arm_pull_up_negative', 'One-arm pull-up negative', 'reps', 8, 24, 'Can lower on one arm with shoulder depression and elbow control.'],
                    ['one_arm_pull_up', 'One-arm pull-up', 'reps', 12, 36, 'Can pull from a one-arm hang to the top with repeatable control.'],
                ],
            ],
            'dip' => [
                'label' => 'Dip',
                'nodes' => [
                    ['no_support', 'No support yet', 'quality', 1, 3, 'Cannot yet hold bodyweight on bars, so the first unlock is stable top support.'],
                    ['top_support', 'Top support', 'hold_seconds', 2, 5, 'Can support bodyweight at the top with locked elbows and depressed shoulders.'],
                    ['assisted_dip', 'Assisted dip', 'reps', 3, 7, 'Can move through the dip pattern with band, foot, or machine assistance.'],
                    ['first_dip', 'First dip', 'reps', 4, 9, 'Can complete one controlled bodyweight dip.'],
                    ['multiple_dips', 'Multiple dips', 'reps', 4, 10, 'Can repeat dips while keeping shoulders stable.'],
                    ['deep_dip_capacity', 'Deep dip capacity', 'reps', 5, 14, 'Can use deeper range without shoulder irritation or loss of control.'],
                    ['ring_support', 'Ring support', 'hold_seconds', 4, 12, 'Can stabilize turned-out rings before loading ring dips.'],
                    ['ring_dip', 'Ring dip', 'reps', 6, 18, 'Can dip on rings with stable shoulders and no uncontrolled swing.'],
                    ['weighted_dip', 'Weighted dip', 'load', 6, 18, 'Can add external load while preserving depth and lockout.'],
                ],
            ],
            'row' => [
                'label' => 'Row',
                'nodes' => [
                    ['no_horizontal_pull', 'No horizontal pull yet', 'quality', 1, 3, 'Cannot yet row with control, so the first unlock is a high-angle bodyweight row.'],
                    ['high_incline_row', 'High incline row', 'reps', 2, 5, 'Can pull the chest to a high bar or rings with bodyline control.'],
                    ['bodyweight_row', 'Bodyweight row', 'reps', 3, 7, 'Can row at a moderate body angle through a repeatable range.'],
                    ['low_bar_row', 'Low bar row', 'reps', 4, 10, 'Can make the row harder by lowering the anchor angle.'],
                    ['ring_row', 'Ring row', 'reps', 4, 10, 'Can control rotating handles and keep scapular rhythm.'],
                    ['feet_elevated_row', 'Feet-elevated row', 'reps', 5, 12, 'Can handle a harder body angle with stable hips.'],
                    ['archer_row', 'Archer row', 'reps', 5, 14, 'Can bias one side while the opposite arm assists.'],
                    ['tuck_front_lever_row', 'Tuck front lever row', 'reps', 6, 18, 'Can combine rowing strength with a tucked lever body shape.'],
                ],
            ],
            'bodyline' => [
                'label' => 'Bodyline',
                'nodes' => [
                    ['dead_bug', 'Dead bug', 'reps', 1, 3, 'Can keep the lower back controlled while moving arms or legs.'],
                    ['tuck_hollow_hold', 'Tuck hollow hold', 'hold_seconds', 2, 5, 'Can hold a shortened hollow shape without neck or hip-flexor dominance.'],
                    ['hollow_body_hold', 'Hollow body hold', 'hold_seconds', 3, 8, 'Can maintain a full hollow body position with clean breathing.'],
                    ['hollow_rocks', 'Hollow rocks', 'reps', 3, 8, 'Can keep the hollow shape while adding motion.'],
                    ['reverse_bodyline_hold', 'Reverse bodyline hold', 'hold_seconds', 2, 6, 'Can control posterior-chain tension for balanced bodyline work.'],
                    ['lever_bodyline', 'Lever bodyline', 'hold_seconds', 4, 12, 'Can apply hollow and posterior tension to harder straight-body skills.'],
                ],
            ],
            'support' => [
                'label' => 'Support',
                'nodes' => [
                    ['no_support', 'No support yet', 'quality', 1, 3, 'Cannot yet support bodyweight on hands, so the first unlock is assisted support.'],
                    ['box_support', 'Box support', 'hold_seconds', 1, 4, 'Can support part of bodyweight with the feet assisting.'],
                    ['parallel_bar_support', 'Parallel bar support', 'hold_seconds', 2, 6, 'Can hold bodyweight between stable handles.'],
                    ['ring_support', 'Ring support', 'hold_seconds', 4, 12, 'Can stabilize rings without shaking out of position.'],
                    ['l_support_prep', 'L-support prep', 'hold_seconds', 4, 12, 'Can lift the legs into a compressed support shape.'],
                    ['straight_arm_support', 'Straight-arm support', 'hold_seconds', 5, 16, 'Can keep locked elbows and depressed shoulders under higher straight-arm demand.'],
                ],
            ],
            'lower_body' => [
                'label' => 'Lower body',
                'nodes' => [
                    ['bodyweight_squat', 'Bodyweight squat', 'reps', 1, 4, 'Can squat with comfortable depth and balance.'],
                    ['goblet_squat', 'Goblet squat', 'load', 2, 6, 'Can add a front-loaded weight while keeping posture and depth.'],
                    ['barbell_squat_base', 'Barbell squat base', 'load', 3, 8, 'Can use barbell loading with stable bracing and range.'],
                    ['loaded_squat_capacity', 'Loaded squat capacity', 'load', 4, 12, 'Can produce enough loaded squat strength to support harder single-leg progressions.'],
                    ['split_squat', 'Split squat', 'reps', 3, 8, 'Can control unilateral stance and knee tracking.'],
                    ['step_down', 'Step-down', 'reps', 3, 8, 'Can lower from a box with balance and eccentric knee control.'],
                    ['nordic_curl_negative', 'Nordic curl negative', 'reps', 6, 16, 'Can lower under hamstring control with safe assistance.'],
                    ['nordic_curl', 'Nordic curl', 'reps', 10, 28, 'Can complete a controlled assisted or full nordic curl variation.'],
                ],
            ],
            'compression' => [
                'label' => 'Compression',
                'nodes' => [
                    ['tuck_support', 'Tuck support', 'hold_seconds', 2, 5, 'Can support bodyweight while lifting bent legs.'],
                    ['tuck_l_sit', 'Tuck L-sit', 'hold_seconds', 3, 8, 'Can hold a compact L-sit shape with stable shoulders.'],
                    ['one_leg_l_sit', 'One-leg L-sit', 'hold_seconds', 4, 10, 'Can extend one leg without collapsing the support position.'],
                    ['full_l_sit', 'Full L-sit', 'hold_seconds', 5, 14, 'Can hold both legs extended with clean shoulder and hip position.'],
                    ['v_sit_prep', 'V-sit prep', 'hold_seconds', 6, 18, 'Can raise the legs above horizontal with active compression.'],
                    ['compression_lift', 'Compression lift', 'reps', 4, 12, 'Can lift the legs from the floor or blocks with active hip compression.'],
                ],
            ],
            'handstand' => [
                'label' => 'Handstand',
                'nodes' => [
                    ['wall_plank', 'Wall plank', 'hold_seconds', 1, 4, 'Can tolerate partial inversion and hand pressure with bodyline control.'],
                    ['chest_to_wall_handstand', 'Chest-to-wall handstand', 'hold_seconds', 3, 8, 'Can hold a stacked wall line with active shoulders.'],
                    ['wall_handstand_shoulder_taps', 'Wall handstand shoulder taps', 'reps', 4, 10, 'Can shift weight in a wall handstand without collapsing.'],
                    ['freestanding_kick_up', 'Freestanding kick-up', 'hold_seconds', 4, 12, 'Can enter a freestanding attempt with a repeatable kick-up.'],
                    ['freestanding_handstand', 'Freestanding handstand', 'hold_seconds', 8, 24, 'Can hold balance without wall support.'],
                    ['pike_push_up', 'Pike push-up', 'reps', 3, 8, 'Can press vertically enough to prepare for handstand push-up loading.'],
                    ['wall_hspu_negative', 'Wall HSPU negative', 'reps', 4, 12, 'Can lower in a wall handstand with control.'],
                    ['partial_wall_hspu', 'Partial wall HSPU', 'reps', 5, 14, 'Can press through a reduced handstand push-up range.'],
                    ['full_wall_hspu', 'Full wall HSPU', 'reps', 6, 18, 'Can press through full wall-supported range.'],
                    ['deep_handstand_push_up', 'Deep handstand push-up', 'reps', 8, 24, 'Can use deficit range while preserving shoulder position.'],
                    ['freestanding_handstand_push_up', 'Freestanding handstand push-up', 'reps', 12, 36, 'Can press while balancing without wall support.'],
                ],
            ],
            'hspu' => [
                'label' => 'Handstand push-up',
                'nodes' => [
                    ['pike_push_up', 'Pike push-up', 'reps', 3, 8, 'Can press vertically with hips piked and shoulders comfortable.'],
                    ['elevated_pike_push_up', 'Elevated pike push-up', 'reps', 4, 10, 'Can increase vertical pressing demand by elevating the feet.'],
                    ['wall_hspu_negative', 'Wall HSPU negative', 'reps', 4, 12, 'Can lower in a wall handstand without losing line.'],
                    ['partial_wall_hspu', 'Partial wall HSPU', 'reps', 5, 14, 'Can press through a controlled partial range.'],
                    ['full_wall_hspu', 'Full wall HSPU', 'reps', 6, 18, 'Can complete wall-supported reps through full range.'],
                    ['deep_handstand_push_up', 'Deep handstand push-up', 'reps', 8, 24, 'Can use deficit range with shoulder control.'],
                    ['freestanding_handstand_push_up', 'Freestanding handstand push-up', 'reps', 12, 36, 'Can combine balance and vertical pressing strength.'],
                ],
            ],
            'muscle_up' => [
                'label' => 'Muscle-up',
                'nodes' => [
                    ['three_by_eight_pull_ups', '3x8 pull-ups', 'reps', 4, 10, 'Can handle enough pull-up volume to start power work.'],
                    ['explosive_pull_up', 'Explosive pull-up', 'reps', 4, 10, 'Can accelerate above a normal pull-up height.'],
                    ['chest_to_bar_pull_up', 'Chest-to-bar pull-up', 'reps', 5, 12, 'Can pull high enough for transition preparation.'],
                    ['high_pull_up', 'High pull-up', 'reps', 5, 14, 'Can pull toward lower chest or upper stomach.'],
                    ['band_assisted_muscle_up', 'Band-assisted muscle-up', 'reps', 4, 12, 'Can practice the full transition with assistance.'],
                    ['negative_muscle_up', 'Negative muscle-up', 'reps', 4, 12, 'Can lower through the transition under control.'],
                    ['strict_muscle_up', 'Muscle-up', 'reps', 8, 24, 'Can pull, transition, and dip without momentum reliance.'],
                    ['weighted_muscle_up', 'Weighted muscle-up', 'load', 10, 30, 'Can add external load while keeping the transition smooth.'],
                ],
            ],
            'front_lever' => [
                'label' => 'Front lever',
                'nodes' => [
                    ['tuck_front_lever', 'Tuck front lever', 'hold_seconds', 4, 10, 'Can hold a tucked lever with shoulders depressed.'],
                    ['advanced_tuck_front_lever', 'Advanced tuck front lever', 'hold_seconds', 5, 14, 'Can open the hip angle while keeping the back near horizontal.'],
                    ['one_leg_front_lever', 'One-leg front lever', 'hold_seconds', 6, 18, 'Can extend one leg while preserving lever line.'],
                    ['half_lay_front_lever', 'Half-lay front lever', 'hold_seconds', 6, 18, 'Can use a shortened straight-leg lever shape with control.'],
                    ['straddle_front_lever', 'Straddle front lever', 'hold_seconds', 8, 24, 'Can hold a straddled lever with stable scapulae.'],
                    ['full_front_lever', 'Full front lever', 'hold_seconds', 12, 36, 'Can hold a full straight-body front lever.'],
                ],
            ],
            'back_lever' => [
                'label' => 'Back lever',
                'nodes' => [
                    ['skin_the_cat_prep', 'Skin-the-cat prep', 'quality', 3, 8, 'Can move through shoulder extension safely with rings or bar.'],
                    ['tuck_back_lever', 'Tuck back lever', 'hold_seconds', 4, 10, 'Can hold a tucked back lever with shoulder comfort.'],
                    ['advanced_tuck_back_lever', 'Advanced tuck back lever', 'hold_seconds', 5, 14, 'Can open the tuck while staying level.'],
                    ['straddle_back_lever', 'Straddle back lever', 'hold_seconds', 8, 24, 'Can hold a straddled back lever without elbow bend.'],
                    ['full_back_lever', 'Full back lever', 'hold_seconds', 12, 36, 'Can hold a full straight-body back lever.'],
                ],
            ],
            'planche' => [
                'label' => 'Planche',
                'nodes' => [
                    ['planche_lean', 'Planche lean', 'hold_seconds', 4, 10, 'Can tolerate forward shoulder lean with locked elbows.'],
                    ['frog_stand', 'Frog stand', 'hold_seconds', 3, 8, 'Can balance on hands with compact hip support.'],
                    ['tuck_planche', 'Tuck planche', 'hold_seconds', 6, 18, 'Can support a tucked planche with scapular protraction.'],
                    ['advanced_tuck_planche', 'Advanced tuck planche', 'hold_seconds', 8, 24, 'Can open the tuck while keeping hips elevated.'],
                    ['straddle_planche', 'Straddle planche', 'hold_seconds', 12, 36, 'Can hold a straddled planche with locked elbows.'],
                    ['full_planche', 'Full planche', 'hold_seconds', 16, 48, 'Can hold a full straight-body planche.'],
                ],
            ],
            'one_arm_pull_up' => [
                'label' => 'One-arm pull-up',
                'nodes' => [
                    ['three_by_eight_pull_ups', '3x8 pull-ups', 'reps', 4, 10, 'Can handle enough vertical-pull volume for one-arm preparation.'],
                    ['weighted_pull_up', 'Weighted pull-up', 'load', 6, 18, 'Can build high-force bilateral pulling strength.'],
                    ['archer_pull_up', 'Archer pull-up', 'reps', 6, 16, 'Can bias one side while the opposite arm assists.'],
                    ['typewriter_pull_up', 'Typewriter pull-up', 'reps', 6, 18, 'Can shift across the top position under control.'],
                    ['assisted_one_arm_pull_up', 'Assisted one-arm pull-up', 'reps', 8, 20, 'Can train the one-arm path with measured assistance.'],
                    ['one_arm_pull_up_negative', 'One-arm pull-up negative', 'reps', 8, 24, 'Can lower from the top position on one arm without elbow irritation.'],
                    ['one_arm_pull_up', 'One-arm pull-up', 'reps', 12, 36, 'Can complete a controlled one-arm rep.'],
                ],
            ],
            'pistol_squat' => [
                'label' => 'Pistol squat',
                'nodes' => [
                    ['split_squat', 'Split squat', 'reps', 3, 8, 'Can control unilateral stance with stable knee tracking.'],
                    ['box_pistol', 'Box pistol', 'reps', 4, 10, 'Can sit to a box on one leg with balance.'],
                    ['assisted_pistol', 'Assisted pistol', 'reps', 4, 12, 'Can use light hand support through pistol squat range.'],
                    ['pistol_negative', 'Pistol negative', 'reps', 5, 14, 'Can lower on one leg under control.'],
                    ['full_pistol_squat', 'Full pistol squat', 'reps', 8, 24, 'Can complete a full single-leg squat without assistance.'],
                    ['weighted_pistol', 'Weighted pistol', 'load', 10, 30, 'Can add external load while keeping balance and depth.'],
                ],
            ],
            'human_flag' => [
                'label' => 'Human flag',
                'nodes' => [
                    ['side_plank', 'Side plank', 'hold_seconds', 2, 6, 'Can hold lateral trunk tension without shoulder discomfort.'],
                    ['vertical_flag_hold', 'Vertical flag hold', 'hold_seconds', 4, 12, 'Can support the body vertically on a pole or ladder setup.'],
                    ['tuck_human_flag', 'Tuck human flag', 'hold_seconds', 6, 18, 'Can hold a compact flag shape with stable push-pull shoulders.'],
                    ['straddle_human_flag', 'Straddle human flag', 'hold_seconds', 8, 24, 'Can extend into a straddled flag with control.'],
                    ['full_human_flag', 'Full human flag', 'hold_seconds', 12, 36, 'Can hold a full horizontal human flag.'],
                ],
            ],
        ];
    }
}
