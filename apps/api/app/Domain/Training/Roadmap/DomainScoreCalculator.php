<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class DomainScoreCalculator
{
    private const array INTERNAL_DOMAINS = [
        'bent_arm_push' => [
            'label' => 'Bent-arm push',
            'display' => 'push_strength',
            'inputs' => ['push_up' => 0.45, 'dip' => 0.55],
        ],
        'vertical_push' => [
            'label' => 'Vertical push',
            'display' => 'push_strength',
            'inputs' => ['push_up' => 0.45, 'dip' => 0.55],
        ],
        'straight_arm_push' => [
            'label' => 'Straight-arm push',
            'display' => 'straight_arm_strength',
            'inputs' => ['planche' => 0.45, 'support' => 0.3, 'push_up' => 0.25],
        ],
        'bent_arm_pull' => [
            'label' => 'Bent-arm pull',
            'display' => 'pull_strength',
            'inputs' => ['pull_up' => 0.6, 'one_arm_pull_up' => 0.25, 'muscle_up' => 0.15],
        ],
        'explosive_pull' => [
            'label' => 'Explosive pull',
            'display' => 'pull_strength',
            'inputs' => ['muscle_up' => 0.55, 'pull_up' => 0.45],
        ],
        'horizontal_pull' => [
            'label' => 'Horizontal pull',
            'display' => 'pull_strength',
            'inputs' => ['row' => 1.0],
        ],
        'straight_arm_pull' => [
            'label' => 'Straight-arm pull',
            'display' => 'straight_arm_strength',
            'inputs' => ['front_lever' => 0.45, 'back_lever' => 0.35, 'row' => 0.2],
        ],
        'grip_hang' => [
            'label' => 'Grip and hang',
            'display' => 'pull_strength',
            'inputs' => ['pull_up' => 0.75, 'one_arm_pull_up' => 0.25],
        ],
        'scapular_control' => [
            'label' => 'Scapular control',
            'display' => 'pull_strength',
            'inputs' => ['row' => 0.35, 'pull_up' => 0.35, 'bodyline' => 0.3],
        ],
        'trunk_rigidity' => [
            'label' => 'Trunk rigidity',
            'display' => 'core_bodyline',
            'inputs' => ['bodyline' => 0.75, 'support' => 0.25],
        ],
        'compression' => [
            'label' => 'Compression',
            'display' => 'core_bodyline',
            'inputs' => ['compression' => 0.7, 'bodyline' => 0.15, 'support' => 0.15],
        ],
        'inversion_balance' => [
            'label' => 'Inversion and balance',
            'display' => 'balance_inversion',
            'inputs' => ['handstand' => 0.7, 'hspu' => 0.3],
        ],
        'lower_body_squat' => [
            'label' => 'Lower-body squat',
            'display' => 'lower_body',
            'inputs' => ['lower_body' => 1.0],
        ],
        'unilateral_leg' => [
            'label' => 'Unilateral leg',
            'display' => 'lower_body',
            'inputs' => ['pistol_squat' => 0.65, 'lower_body' => 0.35],
        ],
        'posterior_chain' => [
            'label' => 'Posterior chain',
            'display' => 'lower_body',
            'inputs' => ['lower_body' => 0.75, 'back_lever' => 0.25],
        ],
    ];

    private const array TISSUE_DOMAINS = [
        'wrist_loaded_extension' => [
            'label' => 'Wrist loaded extension',
            'display' => 'tissue_readiness',
            'inputs' => ['support' => 0.4, 'handstand' => 0.3, 'planche' => 0.3],
            'region' => 'wrist',
            'mobility' => 'wrist_extension',
            'pain_message' => 'Recent or significant wrist pain limits loaded wrist extension.',
        ],
        'elbow_pull_tendon' => [
            'label' => 'Elbow pull tendon',
            'display' => 'tissue_readiness',
            'inputs' => ['pull_up' => 0.4, 'front_lever' => 0.3, 'one_arm_pull_up' => 0.3],
            'region' => 'elbow',
            'pain_message' => 'Recent or significant elbow pain limits elbow pull tendon readiness.',
        ],
        'elbow_push_tendon' => [
            'label' => 'Elbow push tendon',
            'display' => 'tissue_readiness',
            'inputs' => ['push_up' => 0.35, 'dip' => 0.35, 'hspu' => 0.3],
            'region' => 'elbow',
            'pain_message' => 'Recent or significant elbow pain limits elbow push tendon readiness.',
        ],
        'shoulder_flexion' => [
            'label' => 'Shoulder flexion',
            'display' => 'balance_inversion',
            'inputs' => ['handstand' => 0.55, 'hspu' => 0.45],
            'region' => 'shoulder',
            'mobility' => 'shoulder_flexion',
            'pain_message' => 'Recent or significant shoulder pain limits shoulder flexion.',
        ],
        'shoulder_extension' => [
            'label' => 'Shoulder extension',
            'display' => 'tissue_readiness',
            'inputs' => ['dip' => 0.45, 'back_lever' => 0.35, 'support' => 0.2],
            'region' => 'shoulder',
            'mobility' => 'shoulder_extension',
            'pain_message' => 'Recent or significant shoulder pain limits shoulder extension.',
        ],
        'shoulder_straight_arm' => [
            'label' => 'Shoulder straight-arm',
            'display' => 'straight_arm_strength',
            'inputs' => ['planche' => 0.35, 'front_lever' => 0.35, 'back_lever' => 0.3],
            'region' => 'shoulder',
            'pain_message' => 'Recent or significant shoulder pain limits straight-arm shoulder readiness.',
        ],
        'ankle_dorsiflexion' => [
            'label' => 'Ankle dorsiflexion',
            'display' => 'lower_body',
            'inputs' => ['lower_body' => 0.55, 'pistol_squat' => 0.45],
            'region' => 'ankle',
            'mobility' => 'ankle_dorsiflexion',
            'pain_message' => 'Recent or significant ankle pain limits ankle dorsiflexion.',
        ],
        'recovery_capacity' => [
            'label' => 'Recovery capacity',
            'display' => 'tissue_readiness',
            'inputs' => ['bodyline' => 0.35, 'pull_up' => 0.25, 'dip' => 0.25, 'lower_body' => 0.15],
            'region' => null,
        ],
    ];

    private const array DISPLAY_DOMAINS = [
        'push_strength' => [
            'label' => 'Push strength',
            'domains' => ['bent_arm_push', 'vertical_push', 'straight_arm_push', 'elbow_push_tendon', 'wrist_loaded_extension'],
        ],
        'pull_strength' => [
            'label' => 'Pull strength',
            'domains' => ['bent_arm_pull', 'explosive_pull', 'horizontal_pull', 'grip_hang', 'elbow_pull_tendon'],
        ],
        'straight_arm_strength' => [
            'label' => 'Straight-arm strength',
            'domains' => ['straight_arm_push', 'straight_arm_pull', 'shoulder_straight_arm', 'elbow_pull_tendon', 'wrist_loaded_extension'],
        ],
        'core_bodyline' => [
            'label' => 'Core and bodyline',
            'domains' => ['trunk_rigidity', 'compression'],
        ],
        'balance_inversion' => [
            'label' => 'Balance and inversion',
            'domains' => ['inversion_balance', 'shoulder_flexion', 'wrist_loaded_extension'],
        ],
        'lower_body' => [
            'label' => 'Lower body',
            'domains' => ['lower_body_squat', 'unilateral_leg', 'posterior_chain', 'ankle_dorsiflexion'],
        ],
        'tissue_readiness' => [
            'label' => 'Tissue readiness',
            'domains' => ['wrist_loaded_extension', 'elbow_pull_tendon', 'elbow_push_tendon', 'shoulder_flexion', 'shoulder_extension', 'shoulder_straight_arm', 'ankle_dorsiflexion', 'recovery_capacity'],
        ],
    ];

    private const array LEGACY_DOMAINS = [
        'vertical_pull' => [
            'label' => 'Vertical pull',
            'inputs' => ['pull_up' => 0.55, 'one_arm_pull_up' => 0.25, 'muscle_up' => 0.2],
            'display' => 'pull_strength',
        ],
        'horizontal_pull_straight_arm_pull' => [
            'label' => 'Horizontal pull and straight-arm pull',
            'inputs' => ['row' => 0.35, 'front_lever' => 0.35, 'back_lever' => 0.3],
            'display' => 'straight_arm_strength',
        ],
        'lower_body_strength' => [
            'label' => 'Lower-body strength',
            'inputs' => ['lower_body' => 0.75, 'pistol_squat' => 0.25],
            'display' => 'lower_body',
        ],
    ];

    /**
     * @return array<string, DomainScore>
     */
    public static function fromInput(RoadmapInput $input): array
    {
        return self::fromPlacements(BaselineNodeMapper::fromInput($input), $input);
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     * @return array<string, DomainScore>
     */
    public static function fromPlacements(array $placements, RoadmapInput $input): array
    {
        $scores = [];
        $modifiers = self::modifiers($input);

        foreach (self::INTERNAL_DOMAINS as $domain => $definition) {
            $scores[$domain] = self::scoreDomain(
                domain: $domain,
                label: $definition['label'],
                inputs: $definition['inputs'],
                placements: $placements,
                modifiers: $modifiers,
                displayDomain: $definition['display'],
            );
        }

        foreach (self::TISSUE_DOMAINS as $domain => $definition) {
            $scores[$domain] = self::scoreTissueDomain(
                domain: $domain,
                label: $definition['label'],
                inputs: $definition['inputs'],
                placements: $placements,
                input: $input,
                modifiers: $modifiers,
                displayDomain: $definition['display'],
                region: is_string($definition['region'] ?? null) ? $definition['region'] : null,
                mobilityKey: is_string($definition['mobility'] ?? null) ? $definition['mobility'] : null,
                painMessage: is_string($definition['pain_message'] ?? null) ? $definition['pain_message'] : null,
            );
        }

        foreach (self::DISPLAY_DOMAINS as $domain => $definition) {
            $scores[$domain] = self::scoreDisplayDomain(
                domain: $domain,
                label: $definition['label'],
                domainKeys: $definition['domains'],
                scores: $scores,
                modifiers: $modifiers,
            );
        }

        foreach (self::LEGACY_DOMAINS as $domain => $definition) {
            $scores[$domain] = self::scoreDomain(
                domain: $domain,
                label: $definition['label'],
                inputs: $definition['inputs'],
                placements: $placements,
                modifiers: $modifiers,
                displayDomain: $definition['display'],
                kind: 'compatibility',
            );
        }

        $scores['tissue_tolerance'] = self::scoreTissueTolerance($placements, $input, $modifiers);

        return $scores;
    }

    /**
     * @param  array<string, float>  $inputs
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $modifiers
     */
    private static function scoreDomain(
        string $domain,
        string $label,
        array $inputs,
        array $placements,
        array $modifiers,
        string $displayDomain,
        string $kind = 'internal',
    ): DomainScore {
        $totalWeight = array_sum($inputs);
        $observedWeight = 0.0;
        $weightedScore = 0.0;
        $weightedConfidence = 0.0;
        $contributingInputs = [];
        $missingInputs = [];

        foreach ($inputs as $family => $weight) {
            $placement = $placements[$family] ?? null;

            if ($placement === null) {
                $missingInputs[] = "{$family} placement.";

                continue;
            }

            if ($placement->observedEvidence === []) {
                $missingInputs = [...$missingInputs, ...$placement->missingEvidence];

                continue;
            }

            $observedWeight += $weight;
            $weightedScore += $placement->completionPercentage * $weight;
            $weightedConfidence += $placement->confidenceContribution * $weight;
            $contributingInputs = [...$contributingInputs, ...$placement->observedEvidence];
            $missingInputs = [...$missingInputs, ...$placement->missingEvidence];
        }

        $coverage = $totalWeight > 0.0 ? $observedWeight / $totalWeight : 0.0;
        $score = $observedWeight > 0.0 ? (int) round($weightedScore / $observedWeight) : 0;
        $averageConfidence = $observedWeight > 0.0 ? $weightedConfidence / $observedWeight : 0.0;
        $confidence = self::clamp01(($coverage * 0.65) + ($averageConfidence * 0.35));
        $uncertainty = round(1.0 - $confidence, 2);
        $missingInputs = self::unique($missingInputs);

        return new DomainScore(
            domain: $domain,
            label: $label,
            score: $score,
            confidence: $confidence,
            uncertainty: $uncertainty,
            contributingInputs: self::unique($contributingInputs),
            missingInputs: $missingInputs,
            bottleneck: self::bottleneck($score, $confidence, $uncertainty, $missingInputs),
            modifiers: $modifiers,
            kind: $kind,
            displayDomain: $displayDomain,
            weakLinks: self::weakLinks($domain, $score, $confidence, $missingInputs),
        );
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $modifiers
     */
    private static function scoreTissueTolerance(array $placements, RoadmapInput $input, array $modifiers): DomainScore
    {
        $base = self::scoreDomain(
            domain: 'tissue_tolerance',
            label: 'Tissue tolerance',
            inputs: ['support' => 0.35, 'pull_up' => 0.25, 'dip' => 0.25, 'bodyline' => 0.15],
            placements: $placements,
            modifiers: $modifiers,
            displayDomain: 'tissue_readiness',
            kind: 'compatibility',
        );
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $score = $base->score;
        $confidence = $base->confidence;
        $contributingInputs = $base->contributingInputs;
        $missingInputs = $base->missingInputs;
        $bottleneck = $base->bottleneck;

        if ($painLevel === null) {
            $missingInputs[] = 'Pain level.';
        } else {
            $contributingInputs[] = "Pain level: {$painLevel}/10.";
            $confidence = max($confidence, 0.68);

            if ($painLevel >= 7) {
                $score = min($score, 20);
                $bottleneck = 'High pain is the limiting tissue-tolerance signal; stop progression and keep the plan conservative.';
            } elseif ($painLevel >= 4) {
                $score = min($score, 45);
                $bottleneck = 'Pain is the limiting tissue-tolerance signal; hold progression until it settles.';
            } elseif ($painLevel > 0) {
                $score = min($score, 75);
                $bottleneck = 'Mild pain is present; keep progression conservative until it stays quiet.';
            }
        }

        $confidence = self::clamp01($confidence);
        $uncertainty = round(1.0 - $confidence, 2);
        $missingInputs = self::unique($missingInputs);

        if ($contributingInputs === [] && $painLevel === null) {
            $bottleneck = 'Missing inputs make this domain uncertain.';
        }

        return new DomainScore(
            domain: 'tissue_tolerance',
            label: 'Tissue tolerance',
            score: $score,
            confidence: $confidence,
            uncertainty: $uncertainty,
            contributingInputs: self::unique($contributingInputs),
            missingInputs: $missingInputs,
            bottleneck: $bottleneck,
            modifiers: $modifiers,
            kind: 'compatibility',
            displayDomain: 'tissue_readiness',
            weakLinks: self::weakLinks('tissue_tolerance', $score, $confidence, $missingInputs),
        );
    }

    /**
     * @param  array<string, float>  $inputs
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $modifiers
     */
    private static function scoreTissueDomain(
        string $domain,
        string $label,
        array $inputs,
        array $placements,
        RoadmapInput $input,
        array $modifiers,
        string $displayDomain,
        ?string $region,
        ?string $mobilityKey,
        ?string $painMessage,
    ): DomainScore {
        $base = self::scoreDomain(
            domain: $domain,
            label: $label,
            inputs: $inputs,
            placements: $placements,
            modifiers: $modifiers,
            displayDomain: $displayDomain,
        );
        $score = $base->score;
        $confidence = $base->confidence;
        $contributingInputs = $base->contributingInputs;
        $missingInputs = $base->missingInputs;
        $bottleneck = $base->bottleneck;

        if ($mobilityKey !== null) {
            $mobility = self::mobilityStatus($input, $mobilityKey);

            if ($mobility === null || $mobility === 'not_tested') {
                $missingInputs[] = self::mobilityLabel($mobilityKey).'.';
            } elseif ($mobility === 'blocked') {
                $score = min($score, 20);
                $confidence = max($confidence, 0.72);
                $contributingInputs[] = self::mobilityLabel($mobilityKey).' is blocked.';
                $bottleneck = self::mobilityLabel($mobilityKey).' is the limiting tissue-readiness signal.';
            } elseif ($mobility === 'painful') {
                $score = min($score, 30);
                $confidence = max($confidence, 0.72);
                $contributingInputs[] = self::mobilityLabel($mobilityKey).' is painful.';
                $bottleneck = self::mobilityLabel($mobilityKey).' is the limiting tissue-readiness signal.';
            } elseif ($mobility === 'limited') {
                $score = min($score, 60);
                $confidence = max($confidence, 0.62);
                $contributingInputs[] = self::mobilityLabel($mobilityKey).' is limited.';
                $bottleneck = self::mobilityLabel($mobilityKey).' should stay conservative until it is clearer.';
            } else {
                $contributingInputs[] = self::mobilityLabel($mobilityKey).' is clear.';
                $confidence = max($confidence, 0.58);
            }
        }

        if ($region !== null) {
            $pain = self::regionalPain($input, $region);

            if ($pain['present']) {
                $score = min($score, $pain['cap']);
                $confidence = max($confidence, $pain['confidence']);
                $contributingInputs[] = $painMessage ?? $pain['message'] ?? "Pain limits {$label}.";
                $bottleneck = "{$label} is the limiting tissue-readiness signal.";
            }
        }

        $confidence = self::clamp01($confidence);
        $uncertainty = round(1.0 - $confidence, 2);
        $missingInputs = self::unique($missingInputs);

        if ($contributingInputs === [] && $missingInputs !== []) {
            $bottleneck = 'Missing inputs make this domain uncertain.';
        }

        return new DomainScore(
            domain: $domain,
            label: $label,
            score: $score,
            confidence: $confidence,
            uncertainty: $uncertainty,
            contributingInputs: self::unique($contributingInputs),
            missingInputs: $missingInputs,
            bottleneck: $bottleneck,
            modifiers: $modifiers,
            kind: 'internal',
            displayDomain: $displayDomain,
            weakLinks: self::weakLinks($domain, $score, $confidence, $missingInputs),
        );
    }

    /**
     * @param  list<string>  $domainKeys
     * @param  array<string, DomainScore>  $scores
     * @param  list<string>  $modifiers
     */
    private static function scoreDisplayDomain(string $domain, string $label, array $domainKeys, array $scores, array $modifiers): DomainScore
    {
        $children = array_values(array_filter(
            array_map(static fn (string $domainKey): ?DomainScore => $scores[$domainKey] ?? null, $domainKeys),
        ));

        if ($children === []) {
            return new DomainScore(
                domain: $domain,
                label: $label,
                score: 0,
                confidence: 0.0,
                uncertainty: 1.0,
                contributingInputs: [],
                missingInputs: ["{$label} inputs."],
                bottleneck: 'Missing inputs make this domain uncertain.',
                modifiers: $modifiers,
                kind: 'display',
                displayDomain: $domain,
                weakLinks: [$domain],
            );
        }

        $averageScore = (int) round(array_sum(array_map(static fn (DomainScore $score): int => $score->score, $children)) / count($children));
        $minimumScore = min(array_map(static fn (DomainScore $score): int => $score->score, $children));
        $score = min($averageScore, $minimumScore + 15);
        $confidence = self::clamp01(array_sum(array_map(static fn (DomainScore $score): float => $score->confidence, $children)) / count($children));
        $uncertainty = round(1.0 - $confidence, 2);
        $missingInputs = self::unique(array_merge(...array_map(static fn (DomainScore $score): array => $score->missingInputs, $children)));
        $contributingInputs = self::unique(array_merge(...array_map(static fn (DomainScore $score): array => $score->contributingInputs, $children)));
        $weakLinks = self::displayWeakLinks($children);

        return new DomainScore(
            domain: $domain,
            label: $label,
            score: $score,
            confidence: $confidence,
            uncertainty: $uncertainty,
            contributingInputs: $contributingInputs,
            missingInputs: $missingInputs,
            bottleneck: self::displayBottleneck($label, $score, $confidence, $uncertainty, $missingInputs, $weakLinks),
            modifiers: $modifiers,
            kind: 'display',
            displayDomain: $domain,
            weakLinks: $weakLinks,
        );
    }

    /**
     * @param  list<string>  $missingInputs
     */
    private static function bottleneck(int $score, float $confidence, float $uncertainty, array $missingInputs): string
    {
        if ($missingInputs !== [] && $confidence <= 0.35) {
            return 'Missing inputs make this domain uncertain.';
        }

        if ($score >= 70 && $uncertainty >= 0.35) {
            return 'Strong signal, but missing inputs keep this domain uncertain.';
        }

        if ($score < 40 && $confidence >= 0.65) {
            return 'This is a clear bottleneck from observed tests.';
        }

        if ($uncertainty >= 0.5) {
            return 'More evidence is needed before this domain should drive the roadmap.';
        }

        if ($score >= 70) {
            return 'This domain is currently a strength.';
        }

        if ($score < 40) {
            return 'This domain needs foundation work.';
        }

        return 'This domain has workable but incomplete evidence.';
    }

    /**
     * @param  list<string>  $missingInputs
     * @return list<string>
     */
    private static function weakLinks(string $domain, int $score, float $confidence, array $missingInputs): array
    {
        if ($score < 60 || $confidence < 0.65 || $missingInputs !== []) {
            return [$domain];
        }

        return [];
    }

    /**
     * @param  list<DomainScore>  $children
     * @return list<string>
     */
    private static function displayWeakLinks(array $children): array
    {
        $minimum = min(array_map(static fn (DomainScore $score): int => $score->score, $children));
        $weakLinks = [];

        foreach ($children as $child) {
            if ($child->score <= $minimum + 5 || $child->score < 60 || $child->confidence < 0.65) {
                $weakLinks[] = $child->domain;
            }

            $weakLinks = [...$weakLinks, ...$child->weakLinks];
        }

        return self::unique($weakLinks);
    }

    /**
     * @param  list<string>  $missingInputs
     * @param  list<string>  $weakLinks
     */
    private static function displayBottleneck(
        string $label,
        int $score,
        float $confidence,
        float $uncertainty,
        array $missingInputs,
        array $weakLinks,
    ): string {
        if ($missingInputs !== [] && $confidence <= 0.35) {
            return 'Missing inputs make this domain uncertain.';
        }

        if ($weakLinks !== [] && $score < 50) {
            return "{$label} is limited by a weak link: {$weakLinks[0]}.";
        }

        return self::bottleneck($score, $confidence, $uncertainty, $missingInputs);
    }

    /**
     * @return array{present: bool, cap: int, confidence: float, message: string|null}
     */
    private static function regionalPain(RoadmapInput $input, string $region): array
    {
        $regions = self::arrayValue($input->painFlags['regions'] ?? []);
        $flag = is_array($regions[$region] ?? null) ? $regions[$region] : [];
        $severity = self::stringValue($flag['severity'] ?? null, 'none');
        $status = self::stringValue($flag['status'] ?? null, 'none');
        $message = self::painMessage($region, $severity, $status);

        if (in_array($severity, ['severe'], true) || in_array($status, ['acute'], true)) {
            return ['present' => true, 'cap' => 20, 'confidence' => 0.82, 'message' => $message];
        }

        if (in_array($severity, ['moderate'], true) || in_array($status, ['recent'], true)) {
            return ['present' => true, 'cap' => 45, 'confidence' => 0.74, 'message' => $message];
        }

        if (in_array($severity, ['mild'], true) || in_array($status, ['recurring'], true)) {
            return ['present' => true, 'cap' => 75, 'confidence' => 0.68, 'message' => $message];
        }

        return ['present' => false, 'cap' => 100, 'confidence' => 0.0, 'message' => null];
    }

    private static function painMessage(string $region, string $severity, string $status): string
    {
        $regionLabel = str_replace('_', ' ', $region);

        if (in_array($severity, ['moderate', 'severe'], true) || in_array($status, ['recent', 'acute'], true)) {
            return "Recent or significant {$regionLabel} pain limits {$regionLabel} tissue readiness.";
        }

        return ucfirst($regionLabel).' pain is present; keep related tissue loading conservative.';
    }

    private static function mobilityStatus(RoadmapInput $input, string $mobilityKey): ?string
    {
        $mobility = self::arrayValue($input->goalModules['mobility_checks'] ?? []);
        $status = $mobility[$mobilityKey] ?? null;

        return is_string($status) ? $status : null;
    }

    private static function mobilityLabel(string $key): string
    {
        return match ($key) {
            'wrist_extension' => 'Wrist extension',
            'shoulder_flexion' => 'Shoulder flexion',
            'shoulder_extension' => 'Shoulder extension',
            'ankle_dorsiflexion' => 'Ankle dorsiflexion',
            'pancake_compression' => 'Pancake compression',
            default => str_replace('_', ' ', ucfirst($key)),
        };
    }

    /**
     * @return list<string>
     */
    private static function modifiers(RoadmapInput $input): array
    {
        $modifiers = [];
        $bodyweight = self::numberOrNull($input->profileContext['current_bodyweight_value'] ?? null);
        $bodyweightUnit = self::stringValue($input->profileContext['bodyweight_unit'] ?? null, 'kg');
        $height = self::numberOrNull($input->profileContext['height_value'] ?? null);
        $heightUnit = self::stringValue($input->profileContext['height_unit'] ?? null, 'cm');
        $weightTrend = self::stringValue($input->profileContext['weight_trend'] ?? null, 'unknown');
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null);

        if ($bodyweight !== null && $bodyweight > 0) {
            $modifiers[] = 'Body mass: '.self::formatNumber($bodyweight).$bodyweightUnit.'.';
        }

        if ($height !== null && $height > 0) {
            $modifiers[] = 'Height: '.self::formatNumber($height).$heightUnit.'.';
        }

        if ($weightTrend !== 'unknown') {
            $modifiers[] = "Weight trend: {$weightTrend}.";
        }

        if ($trainingAge !== null) {
            $modifiers[] = "Training age: {$trainingAge} months.";
        }

        return $modifiers;
    }

    private static function clamp01(float $value): float
    {
        return round(max(0.0, min(1.0, $value)), 2);
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private static function formatNumber(float $value): string
    {
        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function unique(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }
}
