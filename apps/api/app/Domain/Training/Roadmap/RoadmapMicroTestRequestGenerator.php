<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapMicroTestRequestGenerator
{
    private const int PRIMARY_LIMIT = 3;

    private const int SECONDARY_LIMIT = 2;

    private const int CANDIDATE_LIMIT = 1;

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @return list<array<string, mixed>>
     */
    public static function fromInput(RoadmapInput $input, array $goalCandidates = []): array
    {
        $targets = self::targetSkills($input, $goalCandidates);
        $requests = [];

        foreach ($targets as $skill => $materiality) {
            $definitions = self::definitions()[$skill] ?? [];
            if ($definitions === []) {
                continue;
            }

            $limit = self::limitFor($materiality);
            $targetRequests = [];

            foreach ($definitions as $definition) {
                if (self::isSatisfied($input, $definition)) {
                    continue;
                }

                $targetRequests[] = self::request($input, $skill, $materiality, $definition);

                if (count($targetRequests) >= $limit) {
                    break;
                }
            }

            $requests = [...$requests, ...$targetRequests];
        }

        return self::dedupeRequests($requests);
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @return array<string, string>
     */
    private static function targetSkills(RoadmapInput $input, array $goalCandidates): array
    {
        $targets = [];

        if ($input->selectedPrimaryGoal !== null) {
            $targets[$input->selectedPrimaryGoal] = 'selected_primary';
        }

        foreach ($input->secondaryInterests as $skill) {
            $targets[$skill] ??= 'selected_secondary';
        }

        foreach ($input->longTermAspirations as $skill) {
            $targets[$skill] ??= 'long_term_aspiration';
        }

        if ($targets !== []) {
            return $targets;
        }

        foreach (['primary', 'secondary', 'accessories', 'future'] as $bucket) {
            foreach (self::arrayList($goalCandidates[$bucket] ?? []) as $candidate) {
                $skill = self::stringValue($candidate['skill'] ?? null, '');
                if ($skill !== '' && isset(self::definitions()[$skill])) {
                    $targets[$skill] ??= 'portfolio_candidate';
                }

                if (count($targets) >= 4) {
                    return $targets;
                }
            }
        }

        return $targets;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function definitions(): array
    {
        return [
            'planche' => [
                self::skillStatusDefinition(
                    key: 'planche.planche_lean_hold',
                    relatedNode: ['id' => 'planche.planche_lean', 'label' => 'Planche lean'],
                    prompt: 'How long can you hold a controlled planche lean with locked elbows and protracted shoulders?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(60),
                    whyItMatters: 'This shows whether the first straight-arm planche loading step is trainable.',
                    statusKey: 'planche',
                    satisfyingStatuses: ['planche_lean', 'frog_stand', 'tuck_planche', 'advanced_tuck_planche', 'straddle_planche', 'full_planche'],
                    missingDelta: -0.09,
                    completedDelta: 0.08,
                ),
                self::mobilityDefinition(
                    key: 'planche.wrist_extension_comfort',
                    relatedNode: ['id' => 'planche.wrist_loaded_extension', 'label' => 'Wrist-loaded extension'],
                    prompt: 'Can your wrists tolerate hands-flat leaning work without sharp pain?',
                    measurementType: 'comfort_status',
                    responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                    whyItMatters: 'Planche progress depends on loaded wrist extension before intensity can increase.',
                    mobilityKey: 'wrist_extension',
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::skillStatusDefinition(
                    key: 'planche.frog_stand_attempt',
                    relatedNode: ['id' => 'planche.frog_stand', 'label' => 'Frog stand'],
                    prompt: 'Can you balance a frog stand attempt, even briefly, without wrist or elbow pain?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(45),
                    whyItMatters: 'This separates early balance exposure from pure pushing strength.',
                    statusKey: 'planche',
                    satisfyingStatuses: ['frog_stand', 'tuck_planche', 'advanced_tuck_planche', 'straddle_planche', 'full_planche'],
                    missingDelta: -0.06,
                    completedDelta: 0.05,
                ),
            ],
            'front_lever' => [
                self::baselineDefinition(
                    key: 'front_lever.active_hang_scapular_depression',
                    relatedNode: ['id' => 'front_lever.active_hang', 'label' => 'Active hang'],
                    prompt: 'Can you hang while actively pulling the shoulders down and away from the ears?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(60),
                    whyItMatters: 'Front lever progress needs scapular depression before harder lever holds.',
                    paths: ['current_level_tests.pull_ups.max_strict_reps', 'current_level_tests.pull_ups.fallback_seconds', 'current_level_tests.passive_hang_seconds'],
                    missingDelta: -0.05,
                    completedDelta: 0.04,
                ),
                self::skillStatusDefinition(
                    key: 'front_lever.tuck_hold',
                    relatedNode: ['id' => 'front_lever.tuck', 'label' => 'Tuck front lever'],
                    prompt: 'What is your best controlled tuck front lever hold?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(45),
                    whyItMatters: 'This places the lever track much more precisely than pull-up reps alone.',
                    statusKey: 'front_lever',
                    satisfyingStatuses: ['tuck_front_lever', 'advanced_tuck_front_lever', 'one_leg_front_lever', 'half_lay_front_lever', 'straddle_front_lever', 'full_front_lever'],
                    missingDelta: -0.1,
                    completedDelta: 0.09,
                ),
                self::baselineDefinition(
                    key: 'front_lever.horizontal_row_capacity',
                    relatedNode: ['id' => 'front_lever.row_capacity', 'label' => 'Horizontal row capacity'],
                    prompt: 'How many controlled horizontal rows can you do at your current setup?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(60),
                    whyItMatters: 'Rows help distinguish general pulling strength from lever-specific pulling gaps.',
                    paths: ['current_level_tests.rows.max_reps'],
                    missingDelta: -0.07,
                    completedDelta: 0.06,
                ),
            ],
            'muscle_up' => [
                self::skillStatusDefinition(
                    key: 'muscle_up.chest_to_bar_pull_up',
                    relatedNode: ['id' => 'muscle_up.chest_to_bar_pull_up', 'label' => 'Chest-to-bar pull-up'],
                    prompt: 'Can you pull high enough for chest-to-bar contact or near-contact?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(20),
                    whyItMatters: 'A muscle-up needs higher pulling power than regular pull-up volume shows.',
                    statusKey: 'muscle_up',
                    satisfyingStatuses: ['chest_to_bar_pull_up', 'high_pull_up', 'band_assisted_muscle_up', 'negative_muscle_up', 'strict_muscle_up'],
                    missingDelta: -0.1,
                    completedDelta: 0.09,
                ),
                self::baselineDefinition(
                    key: 'muscle_up.straight_bar_dip',
                    relatedNode: ['id' => 'muscle_up.straight_bar_dip', 'label' => 'Straight-bar dip'],
                    prompt: 'How many clean straight-bar dip reps can you do?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(30),
                    whyItMatters: 'The top of the muscle-up needs a bar dip pattern, not just ring support.',
                    paths: ['current_level_tests.dips.max_strict_reps', 'current_level_tests.top_support_hold_seconds'],
                    missingDelta: -0.06,
                    completedDelta: 0.05,
                ),
                self::skillStatusDefinition(
                    key: 'muscle_up.transition_drill',
                    relatedNode: ['id' => 'muscle_up.transition', 'label' => 'Transition drill'],
                    prompt: 'Can you perform a low-ring, band-assisted, or jumping muscle-up transition drill?',
                    measurementType: 'status',
                    responseShape: self::statusShape(['not_tested', 'attempted', 'assisted', 'controlled']),
                    whyItMatters: 'The transition often blocks athletes who already have enough pull and dip strength.',
                    statusKey: 'muscle_up',
                    satisfyingStatuses: ['band_assisted_muscle_up', 'negative_muscle_up', 'strict_muscle_up'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::equipmentAwareSkillStatusDefinition(
                    key: 'muscle_up.false_grip_low_ring_signal',
                    relatedNode: ['id' => 'muscle_up.low_ring_transition', 'label' => 'Low-ring signal'],
                    prompt: 'If rings are available, can you hold a false grip or practice a low-ring transition?',
                    measurementType: 'status',
                    responseShape: self::statusShape(['not_tested', 'false_grip_hold', 'low_ring_transition']),
                    whyItMatters: 'Rings can unlock a safer transition path when bar muscle-up timing is not clear.',
                    statusKey: 'muscle_up',
                    satisfyingStatuses: ['band_assisted_muscle_up', 'negative_muscle_up', 'strict_muscle_up'],
                    requiredEquipment: ['rings'],
                    missingDelta: -0.04,
                    completedDelta: 0.04,
                ),
            ],
            'one_arm_pull_up' => [
                self::weightedDefinition('one_arm_pull_up.weighted_pull_up_estimate', 'one_arm_pull_up.weighted_pull_up', 'Weighted pull-up estimate', 'weighted_pull_up', -0.1, 0.09),
                self::skillStatusDefinition(
                    key: 'one_arm_pull_up.archer_pull_up',
                    relatedNode: ['id' => 'one_arm_pull_up.archer_pull_up', 'label' => 'Archer pull-up'],
                    prompt: 'Can you do controlled archer or typewriter pull-up reps on either side?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(20),
                    whyItMatters: 'This shows side-to-side pulling capacity before one-arm-specific loading.',
                    statusKey: 'one_arm_pull_up',
                    satisfyingStatuses: ['archer_pull_up', 'typewriter_pull_up', 'assisted_one_arm_pull_up', 'one_arm_pull_up_negative', 'strict_one_arm_pull_up'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::skillStatusDefinition(
                    key: 'one_arm_pull_up.one_arm_active_hang_comfort',
                    relatedNode: ['id' => 'one_arm_pull_up.one_arm_active_hang', 'label' => 'One-arm active hang comfort'],
                    prompt: 'Can you briefly load one arm in an active hang without elbow or shoulder pain?',
                    measurementType: 'comfort_status',
                    responseShape: self::statusShape(['not_tested', 'comfortable', 'limited', 'painful']),
                    whyItMatters: 'One-arm pull-up work is not useful if one-arm hanging irritates the elbow or shoulder.',
                    statusKey: 'one_arm_pull_up',
                    satisfyingStatuses: ['assisted_one_arm_pull_up', 'one_arm_pull_up_negative', 'strict_one_arm_pull_up'],
                    missingDelta: -0.07,
                    completedDelta: 0.06,
                ),
            ],
            'handstand' => self::handstandDefinitions('handstand'),
            'handstand_push_up' => [
                ...self::handstandDefinitions('handstand_push_up'),
                self::skillStatusDefinition(
                    key: 'handstand_push_up.pike_push_up',
                    relatedNode: ['id' => 'handstand_push_up.pike_push_up', 'label' => 'Pike push-up'],
                    prompt: 'How many controlled pike push-up reps can you do?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(30),
                    whyItMatters: 'This checks vertical pressing strength before wall or freestanding HSPU loading.',
                    statusKey: 'handstand_push_up',
                    satisfyingStatuses: ['pike_push_up', 'elevated_pike_push_up', 'wall_hspu_negative', 'partial_wall_hspu', 'full_wall_hspu', 'freestanding_handstand_push_up', 'deep_handstand_push_up'],
                    missingDelta: -0.09,
                    completedDelta: 0.08,
                ),
            ],
            'pistol_squat' => [
                self::mobilityDefinition(
                    key: 'pistol_squat.ankle_dorsiflexion',
                    relatedNode: ['id' => 'pistol_squat.ankle_dorsiflexion', 'label' => 'Ankle dorsiflexion'],
                    prompt: 'Can you reach deep knee-forward squat positions without heel lift or ankle pain?',
                    measurementType: 'comfort_status',
                    responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                    whyItMatters: 'Pistols need enough ankle range before strength progressions make sense.',
                    mobilityKey: 'ankle_dorsiflexion',
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::skillStatusDefinition(
                    key: 'pistol_squat.single_leg_control',
                    relatedNode: ['id' => 'pistol_squat.box_pistol', 'label' => 'Single-leg squat control'],
                    prompt: 'What is your best controlled single-leg squat or box pistol variation?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(20),
                    whyItMatters: 'This places the single-leg track without assuming barbell or two-leg strength transfers directly.',
                    statusKey: 'pistol_squat',
                    satisfyingStatuses: ['split_squat', 'box_pistol', 'pistol_negative', 'full_pistol_squat'],
                    missingDelta: -0.07,
                    completedDelta: 0.06,
                ),
            ],
            'l_sit' => [
                self::skillStatusDefinition(
                    key: 'l_sit.hold',
                    relatedNode: ['id' => 'l_sit.tuck_l_sit', 'label' => 'L-sit hold'],
                    prompt: 'What is your best controlled tuck or full L-sit hold?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(60),
                    whyItMatters: 'L-sit progress depends on compression and support tolerance, not only hollow body holds.',
                    statusKey: 'l_sit',
                    satisfyingStatuses: ['tuck_l_sit', 'one_leg_l_sit', 'full_l_sit', 'v_sit_prep'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::mobilityDefinition(
                    key: 'l_sit.compression',
                    relatedNode: ['id' => 'l_sit.compression', 'label' => 'Compression'],
                    prompt: 'Can you actively compress in a seated pike position without cramping or pain?',
                    measurementType: 'comfort_status',
                    responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                    whyItMatters: 'Compression range changes whether the next L-sit step should be strength, mobility, or both.',
                    mobilityKey: 'pancake_compression',
                    missingDelta: -0.06,
                    completedDelta: 0.05,
                ),
            ],
            'back_lever' => [
                self::skillStatusDefinition(
                    key: 'back_lever.skin_the_cat_prep',
                    relatedNode: ['id' => 'back_lever.skin_the_cat_prep', 'label' => 'Skin-the-cat prep'],
                    prompt: 'Can you control a skin-the-cat prep or tuck back lever entry?',
                    measurementType: 'status',
                    responseShape: self::statusShape(['not_tested', 'prep', 'tuck', 'advanced_tuck']),
                    whyItMatters: 'Back lever readiness depends on shoulder extension exposure as much as pulling strength.',
                    statusKey: 'back_lever',
                    satisfyingStatuses: ['skin_the_cat_prep', 'tuck_back_lever', 'advanced_tuck_back_lever', 'straddle_back_lever', 'full_back_lever'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::mobilityDefinition(
                    key: 'back_lever.shoulder_extension',
                    relatedNode: ['id' => 'back_lever.shoulder_extension', 'label' => 'Shoulder extension'],
                    prompt: 'Can your shoulders tolerate extension behind the body without pain?',
                    measurementType: 'comfort_status',
                    responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                    whyItMatters: 'Back lever and deep dip work should not progress through painful shoulder extension.',
                    mobilityKey: 'shoulder_extension',
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
            ],
            'human_flag' => [
                self::skillStatusDefinition(
                    key: 'human_flag.side_plank',
                    relatedNode: ['id' => 'human_flag.side_plank', 'label' => 'Side plank'],
                    prompt: 'How long can you hold a strong side plank on each side?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(90),
                    whyItMatters: 'Human flag work needs lateral trunk capacity before harder flag holds.',
                    statusKey: 'human_flag',
                    satisfyingStatuses: ['side_plank', 'tuck_human_flag', 'straddle_human_flag', 'vertical_flag_hold', 'full_human_flag'],
                    missingDelta: -0.06,
                    completedDelta: 0.05,
                ),
                self::skillStatusDefinition(
                    key: 'human_flag.vertical_flag_hold',
                    relatedNode: ['id' => 'human_flag.vertical_flag_hold', 'label' => 'Vertical flag hold'],
                    prompt: 'Can you hold a vertical flag or controlled flag lean on both sides?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(45),
                    whyItMatters: 'This checks whether the shoulder and grip pattern is trainable before harder flag leverage.',
                    statusKey: 'human_flag',
                    satisfyingStatuses: ['vertical_flag_hold', 'tuck_human_flag', 'straddle_human_flag', 'full_human_flag'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
            ],
            'press_to_handstand' => [
                self::skillStatusDefinition(
                    key: 'press_to_handstand.compression_lift',
                    relatedNode: ['id' => 'press_to_handstand.compression_lift', 'label' => 'Compression lift'],
                    prompt: 'Can you lift the legs from a seated pike or straddle compression position?',
                    measurementType: 'reps',
                    responseShape: self::repsShape(30),
                    whyItMatters: 'Press work needs compression strength before lean mechanics can be placed well.',
                    statusKey: 'press_to_handstand',
                    satisfyingStatuses: ['compression_lift', 'elevated_press_lean', 'wall_press_negative', 'straddle_press_negative', 'freestanding_press_to_handstand'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::skillStatusDefinition(
                    key: 'press_to_handstand.elevated_press_lean',
                    relatedNode: ['id' => 'press_to_handstand.elevated_press_lean', 'label' => 'Elevated press lean'],
                    prompt: 'Can you control an elevated press lean without wrist or shoulder pain?',
                    measurementType: 'hold_seconds',
                    responseShape: self::secondsShape(45),
                    whyItMatters: 'The lean tells Leverly whether the press path should start with mobility, compression, or handstand strength.',
                    statusKey: 'press_to_handstand',
                    satisfyingStatuses: ['elevated_press_lean', 'wall_press_negative', 'straddle_press_negative', 'freestanding_press_to_handstand'],
                    missingDelta: -0.08,
                    completedDelta: 0.07,
                ),
                self::mobilityDefinition(
                    key: 'press_to_handstand.overhead_mobility',
                    relatedNode: ['id' => 'press_to_handstand.shoulder_flexion', 'label' => 'Shoulder flexion'],
                    prompt: 'Can you reach an overhead line without ribs flaring or shoulder pain?',
                    measurementType: 'comfort_status',
                    responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                    whyItMatters: 'Press-to-handstand depends on overhead line and compression working together.',
                    mobilityKey: 'shoulder_flexion',
                    missingDelta: -0.06,
                    completedDelta: 0.05,
                ),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function handstandDefinitions(string $target): array
    {
        return [
            self::skillStatusDefinition(
                key: "{$target}.wall_line_hold",
                relatedNode: ['id' => 'handstand.wall_line', 'label' => 'Wall line hold'],
                prompt: 'How long can you hold a clean wall handstand line?',
                measurementType: 'hold_seconds',
                responseShape: self::secondsShape(90),
                whyItMatters: 'A wall line hold separates inversion comfort from balance skill.',
                statusKey: 'handstand',
                satisfyingStatuses: ['chest_to_wall_handstand', 'wall_handstand_shoulder_taps', 'freestanding_kick_up', 'freestanding_handstand'],
                missingDelta: -0.07,
                completedDelta: 0.06,
            ),
            self::mobilityDefinition(
                key: "{$target}.overhead_mobility",
                relatedNode: ['id' => 'handstand.shoulder_flexion', 'label' => 'Shoulder flexion'],
                prompt: 'Can you reach an overhead line without ribs flaring or shoulder pain?',
                measurementType: 'comfort_status',
                responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                whyItMatters: 'A limited overhead line changes the handstand path before balance drills are useful.',
                mobilityKey: 'shoulder_flexion',
                missingDelta: -0.07,
                completedDelta: 0.06,
            ),
            self::mobilityDefinition(
                key: "{$target}.wrist_comfort",
                relatedNode: ['id' => 'handstand.wrist_loaded_extension', 'label' => 'Wrist comfort'],
                prompt: 'Can your wrists tolerate hands-flat handstand loading without sharp pain?',
                measurementType: 'comfort_status',
                responseShape: self::statusShape(['clear', 'limited', 'painful', 'blocked']),
                whyItMatters: 'Handstand loading needs wrist tolerance before volume increases.',
                mobilityKey: 'wrist_extension',
                missingDelta: -0.07,
                completedDelta: 0.06,
            ),
        ];
    }

    /**
     * @param  array{id: string, label: string}  $relatedNode
     * @param  list<string>  $satisfyingStatuses
     * @return array<string, mixed>
     */
    private static function skillStatusDefinition(
        string $key,
        array $relatedNode,
        string $prompt,
        string $measurementType,
        array $responseShape,
        string $whyItMatters,
        string $statusKey,
        array $satisfyingStatuses,
        float $missingDelta,
        float $completedDelta,
    ): array {
        return [
            'type' => 'skill_status',
            'key' => $key,
            'related_node' => $relatedNode,
            'prompt' => $prompt,
            'measurement_type' => $measurementType,
            'response_shape' => $responseShape,
            'why_it_matters' => $whyItMatters,
            'status_key' => $statusKey,
            'satisfying_statuses' => $satisfyingStatuses,
            'missing_delta' => $missingDelta,
            'completed_delta' => $completedDelta,
            'blocking' => false,
        ];
    }

    /**
     * @param  array{id: string, label: string}  $relatedNode
     * @param  list<string>  $satisfyingStatuses
     * @param  list<string>  $requiredEquipment
     * @return array<string, mixed>
     */
    private static function equipmentAwareSkillStatusDefinition(
        string $key,
        array $relatedNode,
        string $prompt,
        string $measurementType,
        array $responseShape,
        string $whyItMatters,
        string $statusKey,
        array $satisfyingStatuses,
        array $requiredEquipment,
        float $missingDelta,
        float $completedDelta,
    ): array {
        return [
            ...self::skillStatusDefinition($key, $relatedNode, $prompt, $measurementType, $responseShape, $whyItMatters, $statusKey, $satisfyingStatuses, $missingDelta, $completedDelta),
            'required_equipment' => $requiredEquipment,
        ];
    }

    /**
     * @param  array{id: string, label: string}  $relatedNode
     * @return array<string, mixed>
     */
    private static function mobilityDefinition(
        string $key,
        array $relatedNode,
        string $prompt,
        string $measurementType,
        array $responseShape,
        string $whyItMatters,
        string $mobilityKey,
        float $missingDelta,
        float $completedDelta,
    ): array {
        return [
            'type' => 'mobility',
            'key' => $key,
            'related_node' => $relatedNode,
            'prompt' => $prompt,
            'measurement_type' => $measurementType,
            'response_shape' => $responseShape,
            'why_it_matters' => $whyItMatters,
            'mobility_key' => $mobilityKey,
            'missing_delta' => $missingDelta,
            'completed_delta' => $completedDelta,
            'blocking' => false,
        ];
    }

    /**
     * @param  array{id: string, label: string}  $relatedNode
     * @param  list<string>  $paths
     * @return array<string, mixed>
     */
    private static function baselineDefinition(
        string $key,
        array $relatedNode,
        string $prompt,
        string $measurementType,
        array $responseShape,
        string $whyItMatters,
        array $paths,
        float $missingDelta,
        float $completedDelta,
    ): array {
        return [
            'type' => 'baseline',
            'key' => $key,
            'related_node' => $relatedNode,
            'prompt' => $prompt,
            'measurement_type' => $measurementType,
            'response_shape' => $responseShape,
            'why_it_matters' => $whyItMatters,
            'paths' => $paths,
            'missing_delta' => $missingDelta,
            'completed_delta' => $completedDelta,
            'blocking' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function weightedDefinition(
        string $key,
        string $nodeId,
        string $nodeLabel,
        string $movement,
        float $missingDelta,
        float $completedDelta,
    ): array {
        return [
            'type' => 'weighted_movement',
            'key' => $key,
            'related_node' => ['id' => $nodeId, 'label' => $nodeLabel],
            'prompt' => 'What is your best recent weighted pull-up set and load?',
            'measurement_type' => 'external_load_reps',
            'response_shape' => [
                'type' => 'object',
                'fields' => [
                    'external_load_value' => ['type' => 'number', 'min' => 0, 'max' => 400],
                    'unit' => ['type' => 'enum', 'options' => ['kg', 'lb']],
                    'reps' => ['type' => 'integer', 'min' => 1, 'max' => 30],
                    'rir' => ['type' => 'integer', 'min' => 0, 'max' => 10],
                ],
            ],
            'why_it_matters' => 'One-arm pull-up timelines depend heavily on pulling strength relative to bodyweight.',
            'movement' => $movement,
            'missing_delta' => $missingDelta,
            'completed_delta' => $completedDelta,
            'blocking' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function isSatisfied(RoadmapInput $input, array $definition): bool
    {
        if (! self::equipmentAvailable($input, $definition)) {
            return true;
        }

        return match ($definition['type'] ?? '') {
            'skill_status' => self::skillStatusSatisfies($input, $definition),
            'mobility' => self::mobilitySatisfies($input, $definition),
            'baseline' => self::baselineSatisfies($input, $definition),
            'weighted_movement' => self::weightedMovementSatisfies($input, $definition),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function request(RoadmapInput $input, string $skill, string $materiality, array $definition): array
    {
        return [
            'key' => self::stringValue($definition['key'] ?? null, ''),
            'target_skill' => $skill,
            'target_label' => self::labelFromSkill($skill),
            'related_node' => $definition['related_node'],
            'prompt' => self::stringValue($definition['prompt'] ?? null, ''),
            'measurement_type' => self::stringValue($definition['measurement_type'] ?? null, 'status'),
            'response_shape' => is_array($definition['response_shape'] ?? null) ? $definition['response_shape'] : self::statusShape(['not_tested', 'tested']),
            'why_it_matters' => self::stringValue($definition['why_it_matters'] ?? null, ''),
            'skip_behavior' => 'Skipping keeps confidence lower, can choose a bridge recommendation, and is not counted as zero ability.',
            'not_tested_behavior' => 'bridge_recommendation',
            'blocking' => (bool) ($definition['blocking'] ?? false),
            'state' => self::state($input, $definition),
            'materiality' => $materiality,
            'confidence_impact' => [
                'missing_delta' => (float) ($definition['missing_delta'] ?? -0.05),
                'completed_delta' => (float) ($definition['completed_delta'] ?? 0.05),
                'not_tested_lowers_confidence' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function state(RoadmapInput $input, array $definition): string
    {
        return match ($definition['type'] ?? '') {
            'skill_status' => self::skillStatus($input, self::stringValue($definition['status_key'] ?? null, '')) === 'not_tested' ? 'not_tested' : 'requested',
            'mobility' => self::mobilityStatus($input, self::stringValue($definition['mobility_key'] ?? null, '')) === 'not_tested' ? 'not_tested' : 'requested',
            default => 'requested',
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function skillStatusSatisfies(RoadmapInput $input, array $definition): bool
    {
        $status = self::skillStatus($input, self::stringValue($definition['status_key'] ?? null, ''));

        return in_array($status, self::stringList($definition['satisfying_statuses'] ?? []), true);
    }

    private static function skillStatus(RoadmapInput $input, string $statusKey): string
    {
        $statuses = self::arrayValue($input->goalModules['skill_statuses'] ?? []);
        $status = self::arrayValue($statuses[$statusKey] ?? []);

        return self::stringValue($status['status'] ?? null, '');
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function mobilitySatisfies(RoadmapInput $input, array $definition): bool
    {
        return self::mobilityStatus($input, self::stringValue($definition['mobility_key'] ?? null, '')) === 'clear';
    }

    private static function mobilityStatus(RoadmapInput $input, string $mobilityKey): string
    {
        $mobilityChecks = self::arrayValue($input->goalModules['mobility_checks'] ?? []);

        return self::stringValue($mobilityChecks[$mobilityKey] ?? null, '');
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function baselineSatisfies(RoadmapInput $input, array $definition): bool
    {
        $data = ['current_level_tests' => $input->baselineTests];

        foreach (self::stringList($definition['paths'] ?? []) as $path) {
            $value = self::valueAtPath($data, $path);

            if (is_numeric($value) && (float) $value > 0.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function weightedMovementSatisfies(RoadmapInput $input, array $definition): bool
    {
        $weighted = self::arrayValue($input->goalModules['weighted_baselines'] ?? []);
        $movementKey = self::stringValue($definition['movement'] ?? null, '');

        foreach (self::arrayList($weighted['movements'] ?? []) as $movement) {
            if (($movement['movement'] ?? null) !== $movementKey) {
                continue;
            }

            if (is_numeric($movement['external_load_value'] ?? null) && is_numeric($movement['reps'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private static function equipmentAvailable(RoadmapInput $input, array $definition): bool
    {
        $required = self::stringList($definition['required_equipment'] ?? []);

        if ($required === []) {
            return true;
        }

        foreach ($required as $equipment) {
            if (in_array($equipment, $input->equipment, true)) {
                return true;
            }
        }

        return false;
    }

    private static function limitFor(string $materiality): int
    {
        return match ($materiality) {
            'selected_primary' => self::PRIMARY_LIMIT,
            'selected_secondary', 'long_term_aspiration' => self::SECONDARY_LIMIT,
            default => self::CANDIDATE_LIMIT,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $requests
     * @return list<array<string, mixed>>
     */
    private static function dedupeRequests(array $requests): array
    {
        $deduped = [];

        foreach ($requests as $request) {
            $key = self::stringValue($request['key'] ?? null, '');
            if ($key === '' || isset($deduped[$key])) {
                continue;
            }

            $deduped[$key] = $request;
        }

        return array_values($deduped);
    }

    /**
     * @return array{type: string, unit: string, min: int, max: int}
     */
    private static function secondsShape(int $max): array
    {
        return ['type' => 'number', 'unit' => 'seconds', 'min' => 0, 'max' => $max];
    }

    /**
     * @return array{type: string, unit: string, min: int, max: int}
     */
    private static function repsShape(int $max): array
    {
        return ['type' => 'integer', 'unit' => 'reps', 'min' => 0, 'max' => $max];
    }

    /**
     * @param  list<string>  $options
     * @return array{type: string, options: list<string>}
     */
    private static function statusShape(array $options): array
    {
        return ['type' => 'enum', 'options' => $options];
    }

    private static function labelFromSkill(string $skill): string
    {
        return ucwords(str_replace('_', ' ', $skill));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function valueAtPath(array $data, string $path): mixed
    {
        $current = $data;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function arrayList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter($value, is_string(...))));
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }
}
