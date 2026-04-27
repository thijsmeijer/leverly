<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapTrainingModuleGenerator
{
    private const array PURPOSE_BY_MODE = [
        'development' => 'development',
        'technical_practice' => 'technical_practice',
        'accessory_transfer' => 'accessory_transfer',
        'foundation' => 'foundation_strength',
        'maintenance' => 'maintenance',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function fromNodeReadiness(NodeReadiness $readiness, string $mode): array
    {
        if ($readiness->status === 'blocked_by_hard_gate') {
            return [];
        }

        $purpose = self::PURPOSE_BY_MODE[$mode] ?? null;
        $node = $readiness->nextNode ?? $readiness->targetNode;

        if ($purpose === null || ! $node->schedulable) {
            return [];
        }

        return [
            self::fromGraphNode(
                node: $node,
                purpose: $purpose,
                moduleId: "{$readiness->skillTrackId}.{$node->slug}.{$purpose}",
                title: self::title($node, $purpose),
                skillTrackId: $readiness->skillTrackId,
                prerequisites: self::readinessPrerequisites($readiness, $node),
            )->toArray(),
        ];
    }

    /**
     * @param  list<string>  $targetSkills
     * @return list<array<string, mixed>>
     */
    public static function foundationModules(RoadmapInput $input, array $targetSkills): array
    {
        $modules = [
            self::manual(
                moduleId: 'foundation.push',
                skillTrackId: 'foundation_push',
                nodeId: 'foundation.push',
                title: 'Push foundation',
                purpose: 'foundation_strength',
                pattern: 'push',
                intensityTier: 'medium',
                fatigueClass: 'strength',
                stressVector: ['bent_arm_push' => 2, 'elbow_push_tendon' => 1, 'trunk_rigidity' => 1],
                dose: ['metric' => 'reps', 'sets' => 3, 'reps' => ['min' => 5, 'max' => 10], 'quality' => 'repeatable full-body tension'],
                compatibleDayTypes: ['push_strength', 'general_skills'],
            ),
            self::manual(
                moduleId: 'foundation.pull',
                skillTrackId: 'foundation_pull',
                nodeId: 'foundation.pull',
                title: 'Pull foundation',
                purpose: 'foundation_strength',
                pattern: 'pull',
                intensityTier: 'medium',
                fatigueClass: 'strength',
                stressVector: ['bent_arm_pull' => 2, 'elbow_pull_tendon' => 1, 'grip_hang' => 1],
                dose: ['metric' => 'reps', 'sets' => 3, 'reps' => ['min' => 4, 'max' => 8], 'quality' => 'controlled shoulder depression'],
                compatibleDayTypes: ['pull_strength', 'general_skills'],
            ),
            self::manual(
                moduleId: 'foundation.lower_body',
                skillTrackId: 'foundation_lower_body',
                nodeId: 'foundation.lower_body',
                title: 'Lower-body foundation',
                purpose: 'foundation_strength',
                pattern: 'legs',
                intensityTier: 'medium',
                fatigueClass: 'strength',
                stressVector: ['lower_body' => 2, 'ankle_knee' => 1],
                dose: ['metric' => 'reps', 'sets' => 3, 'reps' => ['min' => 6, 'max' => 10], 'quality' => 'balanced depth and knee tracking'],
                compatibleDayTypes: ['legs', 'general_skills'],
            ),
            self::manual(
                moduleId: 'foundation.trunk_bodyline',
                skillTrackId: 'foundation_trunk_bodyline',
                nodeId: 'foundation.trunk_bodyline',
                title: 'Trunk and bodyline foundation',
                purpose: 'foundation_strength',
                pattern: 'trunk',
                intensityTier: 'low',
                fatigueClass: 'static_strength',
                stressVector: ['trunk_rigidity' => 2],
                dose: ['metric' => 'hold_seconds', 'sets' => 3, 'hold_seconds' => ['min' => 15, 'max' => 35], 'quality' => 'quiet ribs and pelvis'],
                compatibleDayTypes: ['general_skills', 'legs', 'pull_strength', 'push_strength'],
            ),
            self::manual(
                moduleId: 'foundation.mobility',
                skillTrackId: 'foundation_mobility',
                nodeId: 'foundation.mobility',
                title: 'Mobility prep',
                purpose: 'mobility_prep',
                pattern: 'mobility',
                intensityTier: 'low',
                fatigueClass: 'mobility',
                stressVector: self::mobilityStress($input),
                dose: ['metric' => 'quality', 'practice_minutes' => ['min' => 6, 'max' => 10], 'quality' => 'positions needed by the active portfolio'],
                compatibleDayTypes: ['general_skills', 'legs', 'pull_strength', 'push_strength'],
            ),
            self::manual(
                moduleId: 'foundation.tissue_prep',
                skillTrackId: 'foundation_tissue_prep',
                nodeId: 'foundation.tissue_prep',
                title: 'Tissue capacity prep',
                purpose: 'tissue_capacity',
                pattern: 'mobility',
                intensityTier: 'low',
                fatigueClass: 'tendon',
                stressVector: ['wrist_extension' => 1, 'elbow_pull_tendon' => 1, 'elbow_push_tendon' => 1, 'shoulder_flexion' => 1],
                dose: ['metric' => 'quality', 'practice_minutes' => ['min' => 6, 'max' => 12], 'quality' => 'low-irritation joint preparation'],
                compatibleDayTypes: ['general_skills', 'pull_strength', 'push_strength'],
            ),
        ];

        foreach (array_values(array_unique($targetSkills)) as $skill) {
            $modules = [...$modules, ...self::targetFoundationModules($skill)];
        }

        return array_map(
            static fn (RoadmapTrainingModule $module): array => $module->toArray(),
            $modules,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function exampleModules(): array
    {
        $examples = [
            ['planche', 'planche', 'planche_lean', 'tissue_capacity'],
            ['front_lever', 'front_lever', 'tuck_front_lever', 'development'],
            ['muscle_up', 'muscle_up', 'high_pull_up', 'accessory_transfer'],
            ['handstand', 'handstand', 'chest_to_wall_handstand', 'technical_practice'],
            ['handstand_push_up', 'hspu', 'wall_hspu_negative', 'development'],
            ['one_arm_pull_up', 'one_arm_pull_up', 'assisted_one_arm_pull_up', 'development'],
            ['l_sit', 'compression', 'full_l_sit', 'accessory_transfer'],
            ['pistol_squat', 'pistol_squat', 'full_pistol_squat', 'development'],
            ['weighted_pull_up', 'pull_up', 'weighted_pull_up', 'development'],
            ['weighted_dip', 'dip', 'weighted_dip', 'development'],
            ['human_flag', 'human_flag', 'tuck_human_flag', 'development'],
        ];

        $modules = [];
        foreach ($examples as [$skill, $family, $slug, $purpose]) {
            $node = ProgressionGraphRegistry::node($family, $slug);
            if ($node === null) {
                continue;
            }

            $modules[] = self::fromGraphNode(
                node: $node,
                purpose: $purpose,
                moduleId: "{$skill}.{$slug}.example",
                skillTrackId: $skill,
            )->toArray();
        }

        return $modules;
    }

    /**
     * @param  list<array<string, string>>|null  $prerequisites
     */
    private static function fromGraphNode(
        ProgressionGraphNode $node,
        string $purpose,
        ?string $moduleId = null,
        ?string $title = null,
        ?string $skillTrackId = null,
        ?array $prerequisites = null,
    ): RoadmapTrainingModule {
        $intensityTier = self::intensityTier($node, $purpose);
        $pattern = self::pattern($node);

        return new RoadmapTrainingModule(
            moduleId: $moduleId ?? "{$node->skillTrackId}.{$node->slug}.{$purpose}",
            skillTrackId: $skillTrackId ?? $node->skillTrackId,
            nodeId: $node->nodeId,
            title: $title ?? self::title($node, $purpose),
            purpose: $purpose,
            pattern: $pattern,
            intensityTier: $intensityTier,
            fatigueClass: self::moduleFatigueClass($node, $purpose),
            stressVector: self::normalStressVector($node->stressVector),
            dose: self::dose($node, $purpose, $intensityTier),
            timeCostMinutes: self::timeCost($purpose, $intensityTier),
            exposureTargets: self::exposureTargets($purpose),
            recoveryRequirements: [
                'min_hours_by_stress_axis' => self::recoveryHours($node->stressVector, $intensityTier),
            ],
            allowedSessionSlots: self::allowedSessionSlots($purpose),
            compatibleDayTypes: self::compatibleDayTypes($pattern, $purpose),
            prerequisites: $prerequisites ?? self::nodePrerequisites($node),
            progressionRule: self::progressionRule($node),
        );
    }

    /**
     * @param  array<string, int>  $stressVector
     * @param  array<string, mixed>  $dose
     * @param  list<string>  $compatibleDayTypes
     */
    private static function manual(
        string $moduleId,
        string $skillTrackId,
        string $nodeId,
        string $title,
        string $purpose,
        string $pattern,
        string $intensityTier,
        string $fatigueClass,
        array $stressVector,
        array $dose,
        array $compatibleDayTypes,
    ): RoadmapTrainingModule {
        return new RoadmapTrainingModule(
            moduleId: $moduleId,
            skillTrackId: $skillTrackId,
            nodeId: $nodeId,
            title: $title,
            purpose: $purpose,
            pattern: $pattern,
            intensityTier: $intensityTier,
            fatigueClass: $fatigueClass,
            stressVector: self::normalStressVector($stressVector),
            dose: $dose,
            timeCostMinutes: self::timeCost($purpose, $intensityTier),
            exposureTargets: self::exposureTargets($purpose),
            recoveryRequirements: [
                'min_hours_by_stress_axis' => self::recoveryHours($stressVector, $intensityTier),
            ],
            allowedSessionSlots: self::allowedSessionSlots($purpose),
            compatibleDayTypes: $compatibleDayTypes,
            prerequisites: [
                ['type' => 'quality_gate', 'id' => $nodeId, 'label' => 'Use a controlled, pain-free setup.'],
            ],
            progressionRule: [
                'action' => self::manualProgressionAction($dose),
                'trigger' => 'Add one small lever only after all target exposures are repeatable.',
                'limit' => 'Do not progress through pain or sharp form breakdown.',
            ],
        );
    }

    /**
     * @return list<RoadmapTrainingModule>
     */
    private static function targetFoundationModules(string $skill): array
    {
        return match ($skill) {
            'muscle_up', 'weighted_muscle_up' => self::graphNodeModules([
                [
                    'moduleId' => 'foundation.muscle_up.high_pull',
                    'family' => 'muscle_up',
                    'slug' => 'high_pull_up',
                    'purpose' => 'accessory_transfer',
                    'title' => 'High-pull bridge',
                ],
            ]),
            'planche' => self::graphNodeModules([
                [
                    'moduleId' => 'foundation.planche.planche_lean',
                    'family' => 'planche',
                    'slug' => 'planche_lean',
                    'purpose' => 'tissue_capacity',
                    'title' => 'Planche lean capacity',
                ],
            ]),
            default => [],
        };
    }

    /**
     * @param  list<array{moduleId: string, family: string, slug: string, purpose: string, title: string}>  $definitions
     * @return list<RoadmapTrainingModule>
     */
    private static function graphNodeModules(array $definitions): array
    {
        $modules = [];

        foreach ($definitions as $definition) {
            $node = ProgressionGraphRegistry::node($definition['family'], $definition['slug']);
            if ($node === null) {
                continue;
            }

            $modules[] = self::fromGraphNode(
                node: $node,
                purpose: $definition['purpose'],
                moduleId: $definition['moduleId'],
                title: $definition['title'],
                skillTrackId: $node->skillTrackId,
            );
        }

        return $modules;
    }

    private static function intensityTier(ProgressionGraphNode $node, string $purpose): string
    {
        if ($purpose === 'technical_practice') {
            return in_array($node->fatigueClass, ['high', 'max'], true) ? 'medium' : $node->fatigueClass;
        }

        if ($purpose === 'maintenance' && in_array($node->fatigueClass, ['high', 'max'], true)) {
            return 'medium';
        }

        return in_array($node->fatigueClass, ['low', 'medium', 'high', 'max'], true)
            ? $node->fatigueClass
            : 'medium';
    }

    private static function moduleFatigueClass(ProgressionGraphNode $node, string $purpose): string
    {
        if ($purpose === 'mobility_prep') {
            return 'mobility';
        }

        if ($purpose === 'tissue_capacity') {
            return 'tendon';
        }

        if ($node->metricType === 'hold_seconds' && ! in_array($node->family, ['handstand', 'human_flag'], true)) {
            return 'static_strength';
        }

        if ($node->family === 'muscle_up' || str_contains($node->slug, 'explosive') || str_contains($node->slug, 'high_pull')) {
            return 'power';
        }

        if ($node->family === 'handstand') {
            return 'skill';
        }

        return 'strength';
    }

    private static function pattern(ProgressionGraphNode $node): string
    {
        return match ($node->family) {
            'push_up', 'dip', 'planche', 'hspu', 'support' => 'push',
            'pull_up', 'row', 'front_lever', 'back_lever', 'one_arm_pull_up' => 'pull',
            'lower_body', 'pistol_squat' => 'legs',
            'bodyline' => 'trunk',
            'compression' => 'compression',
            'handstand', 'human_flag' => 'inversion',
            'muscle_up' => 'transition',
            default => 'mobility',
        };
    }

    private static function title(ProgressionGraphNode $node, string $purpose): string
    {
        $prefix = match ($purpose) {
            'development' => 'Develop',
            'technical_practice' => 'Practice',
            'accessory_transfer' => 'Support',
            'foundation_strength' => 'Build',
            'mobility_prep' => 'Prepare',
            'tissue_capacity' => 'Condition',
            'maintenance' => 'Maintain',
            default => 'Train',
        };

        return "{$prefix} {$node->label}";
    }

    /**
     * @return array<string, mixed>
     */
    private static function dose(ProgressionGraphNode $node, string $purpose, string $intensityTier): array
    {
        $sets = match ($purpose) {
            'technical_practice', 'mobility_prep' => 4,
            'maintenance' => 2,
            default => 3,
        };

        return match ($node->metricType) {
            'hold_seconds' => [
                'metric' => 'hold_seconds',
                'sets' => $sets,
                'hold_seconds' => self::holdRange($intensityTier),
                'rest_seconds' => self::restSeconds($intensityTier),
            ],
            'load' => [
                'metric' => 'load',
                'sets' => max(3, $sets),
                'reps' => ['min' => 2, 'max' => 5],
                'load_target' => 'submaximal_external_load',
                'rest_seconds' => self::restSeconds($intensityTier),
            ],
            'quality' => [
                'metric' => 'quality',
                'practice_minutes' => ['min' => 6, 'max' => 12],
                'quality' => $node->measurementRule,
                'rest_seconds' => 60,
            ],
            default => [
                'metric' => 'reps',
                'sets' => $sets,
                'reps' => self::repRange($intensityTier),
                'rest_seconds' => self::restSeconds($intensityTier),
            ],
        };
    }

    /**
     * @return array{min: int, max: int}
     */
    private static function repRange(string $intensityTier): array
    {
        return match ($intensityTier) {
            'max' => ['min' => 1, 'max' => 3],
            'high' => ['min' => 3, 'max' => 6],
            'medium' => ['min' => 5, 'max' => 10],
            default => ['min' => 6, 'max' => 12],
        };
    }

    /**
     * @return array{min: int, max: int}
     */
    private static function holdRange(string $intensityTier): array
    {
        return match ($intensityTier) {
            'max' => ['min' => 4, 'max' => 8],
            'high' => ['min' => 6, 'max' => 12],
            'medium' => ['min' => 10, 'max' => 20],
            default => ['min' => 15, 'max' => 30],
        };
    }

    private static function restSeconds(string $intensityTier): int
    {
        return match ($intensityTier) {
            'max' => 180,
            'high' => 150,
            'medium' => 90,
            default => 45,
        };
    }

    /**
     * @return array{min: int, max: int}
     */
    private static function timeCost(string $purpose, string $intensityTier): array
    {
        if ($purpose === 'technical_practice') {
            return $intensityTier === 'medium' ? ['min' => 8, 'max' => 14] : ['min' => 6, 'max' => 12];
        }

        if (in_array($purpose, ['mobility_prep', 'tissue_capacity', 'maintenance'], true)) {
            return ['min' => 6, 'max' => 12];
        }

        return match ($intensityTier) {
            'max' => ['min' => 14, 'max' => 24],
            'high' => ['min' => 12, 'max' => 20],
            'medium' => ['min' => 10, 'max' => 16],
            default => ['min' => 8, 'max' => 12],
        };
    }

    /**
     * @return array{min_per_week: int, target_per_week: int, max_per_week: int}
     */
    private static function exposureTargets(string $purpose): array
    {
        return match ($purpose) {
            'technical_practice' => ['min_per_week' => 2, 'target_per_week' => 3, 'max_per_week' => 5],
            'foundation_strength' => ['min_per_week' => 2, 'target_per_week' => 2, 'max_per_week' => 4],
            'mobility_prep' => ['min_per_week' => 2, 'target_per_week' => 4, 'max_per_week' => 7],
            'tissue_capacity' => ['min_per_week' => 2, 'target_per_week' => 3, 'max_per_week' => 5],
            'maintenance' => ['min_per_week' => 1, 'target_per_week' => 1, 'max_per_week' => 2],
            'accessory_transfer' => ['min_per_week' => 1, 'target_per_week' => 2, 'max_per_week' => 3],
            default => ['min_per_week' => 1, 'target_per_week' => 2, 'max_per_week' => 3],
        };
    }

    /**
     * @param  array<string, int>  $stressVector
     * @return array<string, int>
     */
    private static function recoveryHours(array $stressVector, string $intensityTier): array
    {
        $base = match ($intensityTier) {
            'max' => 72,
            'high' => 48,
            'medium' => 24,
            default => 12,
        };
        $hours = [];

        foreach ($stressVector as $axis => $load) {
            if ($load <= 0) {
                continue;
            }

            $hours[$axis] = $base + max(0, $load - 2) * 6;
        }

        return $hours;
    }

    /**
     * @return list<string>
     */
    private static function allowedSessionSlots(string $purpose): array
    {
        return match ($purpose) {
            'mobility_prep', 'tissue_capacity' => ['warmup_prep', 'cooldown_mobility'],
            'technical_practice' => ['skill_a', 'skill_b'],
            'development' => ['skill_a', 'primary_strength'],
            'foundation_strength' => ['primary_strength', 'accessory'],
            'accessory_transfer' => ['skill_b', 'accessory'],
            'maintenance' => ['skill_b', 'accessory'],
            default => ['accessory'],
        };
    }

    /**
     * @return list<string>
     */
    private static function compatibleDayTypes(string $pattern, string $purpose): array
    {
        if (in_array($purpose, ['mobility_prep', 'tissue_capacity'], true)) {
            return ['general_skills', 'legs', 'pull_strength', 'push_strength'];
        }

        return match ($pattern) {
            'pull' => ['pull_skills', 'pull_strength', 'general_skills'],
            'push' => ['push_skills', 'push_strength', 'general_skills'],
            'legs' => ['legs', 'general_skills'],
            'trunk', 'compression' => ['general_skills', 'legs', 'pull_strength', 'push_strength'],
            'inversion' => ['general_skills', 'push_skills', 'push_strength'],
            'transition' => ['pull_skills', 'pull_strength', 'general_skills'],
            default => ['general_skills'],
        };
    }

    /**
     * @return list<array<string, string>>
     */
    private static function nodePrerequisites(ProgressionGraphNode $node): array
    {
        $prerequisites = [
            ['type' => 'graph_gate', 'id' => $node->nodeId, 'label' => $node->unlock],
        ];

        foreach ($node->requiredEquipment as $equipment) {
            $prerequisites[] = ['type' => 'equipment', 'id' => $equipment, 'label' => "Requires {$equipment}."];
        }

        foreach ($node->mobilityRequirements as $requirement) {
            $prerequisites[] = ['type' => 'mobility', 'id' => $requirement, 'label' => "Needs {$requirement}."];
        }

        return $prerequisites;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readinessPrerequisites(NodeReadiness $readiness, ProgressionGraphNode $node): array
    {
        return [
            ['type' => 'current_node', 'id' => $readiness->currentNode->nodeId, 'label' => "Own {$readiness->currentNode->label} first."],
            ...self::nodePrerequisites($node),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function progressionRule(ProgressionGraphNode $node): array
    {
        $action = match ($node->metricType) {
            'hold_seconds' => 'increase_hold_time',
            'load' => 'add_load',
            'quality' => 'harder_variation',
            default => 'increase_reps',
        };

        return [
            'action' => $action,
            'trigger' => 'Progress after all target exposures are repeatable with calm joints and repeatable technique.',
            'limit' => 'Change one lever at a time and hold the node when pain, fatigue, or form breaks down.',
        ];
    }

    /**
     * @param  array<string, mixed>  $dose
     */
    private static function manualProgressionAction(array $dose): string
    {
        return match ($dose['metric'] ?? null) {
            'hold_seconds' => 'increase_hold_time',
            'load' => 'add_load',
            'quality' => 'harder_variation',
            default => 'increase_reps',
        };
    }

    /**
     * @param  array<string, int>  $stressVector
     * @return array<string, int>
     */
    private static function normalStressVector(array $stressVector): array
    {
        $normal = [];

        foreach ($stressVector as $axis => $load) {
            $normal[(string) $axis] = max(0, min(10, (int) $load));
        }

        return $normal;
    }

    /**
     * @return array<string, int>
     */
    private static function mobilityStress(RoadmapInput $input): array
    {
        $checks = is_array($input->goalModules['mobility_checks'] ?? null)
            ? $input->goalModules['mobility_checks']
            : [];

        return [
            'wrist_extension' => in_array($checks['wrist_extension'] ?? null, ['limited', 'blocked', 'painful'], true) ? 2 : 1,
            'shoulder_flexion' => in_array($checks['shoulder_flexion'] ?? null, ['limited', 'blocked', 'painful'], true) ? 2 : 1,
            'shoulder_extension' => in_array($checks['shoulder_extension'] ?? null, ['limited', 'blocked', 'painful'], true) ? 2 : 1,
            'ankle_knee' => in_array($checks['ankle_dorsiflexion'] ?? null, ['limited', 'blocked', 'painful'], true) ? 2 : 1,
            'compression' => in_array($checks['pancake_compression'] ?? null, ['limited', 'blocked', 'painful'], true) ? 2 : 1,
        ];
    }
}
