<?php

declare(strict_types=1);

namespace App\Domain\Training\Support;

use App\Domain\Training\Roadmap\RoadmapAdaptationEvidenceProvider;
use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapPortfolioResult;
use App\Domain\Training\Roadmap\RoadmapResult;

final class CalisthenicsRoadmapSuggester
{
    private const array TARGET_LABELS = [
        'strict_push_up' => 'Push-up',
        'one_arm_push_up' => 'One-arm push-up',
        'strict_pull_up' => 'Pull-up',
        'weighted_pull_up' => 'Weighted pull-up',
        'strict_dip' => 'Dip',
        'ring_dip' => 'Ring dip',
        'weighted_dip' => 'Weighted dip',
        'muscle_up' => 'Muscle-up',
        'weighted_muscle_up' => 'Weighted muscle-up',
        'l_sit' => 'L-sit',
        'v_sit' => 'V-sit',
        'handstand' => 'Handstand',
        'handstand_push_up' => 'Handstand push-up',
        'press_to_handstand' => 'Press to handstand',
        'front_lever' => 'Front lever',
        'back_lever' => 'Back lever',
        'planche' => 'Planche',
        'pistol_squat' => 'Pistol squat',
        'nordic_curl' => 'Nordic curl',
        'one_arm_pull_up' => 'One-arm pull-up',
        'human_flag' => 'Human flag',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function suggest(RoadmapInput $input, bool $includeIntermediate = false): array
    {
        $signals = self::signals($input);

        $unlocked = [];
        $bridge = [];
        $longTerm = [];
        $deferred = [];

        self::placeTrack(
            $signals['has_push_base'],
            $unlocked,
            $bridge,
            self::track(
                'strict_push_up',
                $signals['has_push_base'] ? 'Your pressing baseline can support direct push-up volume.' : 'Build the first clean push-up before heavier pressing goals.',
                ['push_capacity', 'core_bodyline'],
                $signals['has_push_base'] ? 'Build repeatable sets of 8 to 12 reps.' : 'Own incline or knee push-ups with a rigid bodyline.',
                ['handstand', 'strict_dip'],
            ),
        );

        self::placeTrack(
            $signals['has_pull_base'],
            $unlocked,
            $bridge,
            self::track(
                'strict_pull_up',
                $signals['has_pull_up'] ? 'Pull-ups are already in range for direct progression.' : 'Your current pulling number needs a bridge toward the first pull-up.',
                ['pull_capacity', 'row_volume', 'core_bodyline'],
                $signals['has_pull_up'] ? 'Build toward 3 clean sets of 6 to 8.' : 'Accumulate quality pulling volume, scapular control, and controlled negatives.',
                ['l_sit', 'front_lever'],
            ),
        );

        self::placeTrack(
            $signals['has_dip_base'],
            $unlocked,
            $bridge,
            self::track(
                'strict_dip',
                $signals['has_dip'] ? 'Dip reps are ready for focused dip progression.' : 'Build pressing strength before dips become a main target.',
                ['dip_support', 'push_capacity', 'straight_arm_tolerance'],
                $signals['has_dip'] ? 'Build controlled depth for 3 sets of 6 to 8.' : 'Own clean push-ups and pain-free assisted dip depth.',
                ['l_sit', 'ring_dip'],
            ),
        );

        if ($signals['handstand_ready']) {
            $unlocked[] = self::track(
                'handstand',
                'Your pressing, bodyline, shoulder, and wrist signals are ready for regular handstand practice.',
                ['handstand_line', 'core_bodyline', 'mobility_positions'],
                'Build a clean line and controlled balance entries.',
                ['l_sit', 'strict_push_up'],
            );
        } else {
            $bridge[] = self::track(
                'handstand',
                'Start with wrist, shoulder, hollow, and inversion preparation before chasing freestanding balance.',
                ['handstand_line', 'core_bodyline', 'mobility_positions'],
                'Reach pain-free wrist loading, overhead line, and controlled inverted exposure.',
                ['strict_push_up', 'l_sit'],
            );
        }

        if ($signals['l_sit_ready']) {
            $unlocked[] = self::track(
                'l_sit',
                'Your dip and midline tests can support compression work now.',
                ['compression', 'core_bodyline', 'dip_support'],
                'Build a repeatable tuck or full L-sit hold.',
                ['handstand', 'strict_dip'],
            );
        } else {
            $bridge[] = self::track(
                'l_sit',
                'Compression, dip strength, and hollow body control should come before harder seated hold targets.',
                ['compression', 'core_bodyline', 'dip_support'],
                'Own hollow body holds, hanging knee raises, and clean dip strength.',
                ['strict_dip', 'handstand'],
            );
        }

        if ($signals['pistol_ready']) {
            $unlocked[] = self::track(
                'pistol_squat',
                'Your squat pattern and ankle signal can support single-leg progression.',
                ['leg_strength', 'mobility_positions'],
                'Build controlled assisted pistols before chasing full depth reps.',
                ['nordic_curl'],
            );
        } else {
            $bridge[] = self::track(
                'pistol_squat',
                'Leg strength or ankle position should be built before making pistols a main target.',
                ['leg_strength', 'mobility_positions'],
                'Reach stable split squats and pain-free squat depth.',
                ['nordic_curl'],
            );
        }

        self::addAdvancedTracks($signals, $unlocked, $bridge, $longTerm, $deferred);
        self::addRequestedAspirations($input, $unlocked, $bridge, $longTerm, $deferred);

        $baseFocusAreas = self::uniqueStrings(array_slice(array_merge(
            ...array_map(
                fn (array $track): array => is_array($track['base_focus_areas'] ?? null) ? $track['base_focus_areas'] : [],
                [...$unlocked, ...$bridge],
            ),
        ), 0, 4));

        return RoadmapResult::fromTrackBuckets($input, $signals, [
            'level' => self::level($signals),
            'summary' => self::summary($signals),
            'body_context' => self::bodyContext($input),
            'base_focus_areas' => $baseFocusAreas,
            'unlocked_tracks' => self::dedupeTracks($unlocked),
            'bridge_tracks' => self::dedupeTracks($bridge),
            'long_term_tracks' => self::dedupeTracks($longTerm),
            'deferred_tracks' => self::dedupeTracks($deferred),
        ], $includeIntermediate)->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function suggestFromAthleteData(array $data, bool $includeIntermediate = false): array
    {
        return self::suggest(RoadmapInputMapper::fromAthleteData($data), $includeIntermediate);
    }

    /**
     * @return array<string, mixed>
     */
    public static function portfolio(
        RoadmapInput $input,
        bool $includeIntermediate = false,
        ?RoadmapAdaptationEvidenceProvider $adaptationProvider = null,
    ): array {
        return RoadmapPortfolioResult::fromRoadmapSuggestions(
            self::suggest($input, $includeIntermediate),
            $input,
            $adaptationProvider,
        )->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function portfolioFromAthleteData(array $data, bool $includeIntermediate = false): array
    {
        return self::portfolio(RoadmapInputMapper::fromAthleteData($data), $includeIntermediate);
    }

    /**
     * @return list<string>
     */
    public static function activeSkillSlugs(array $suggestions): array
    {
        $goalCandidates = is_array($suggestions['goal_candidates'] ?? null) ? $suggestions['goal_candidates'] : [];
        $primaryCandidates = is_array($goalCandidates['primary'] ?? null) ? $goalCandidates['primary'] : [];
        $candidateSlugs = self::trackSlugs($primaryCandidates);

        if ($candidateSlugs !== []) {
            return $candidateSlugs;
        }

        return self::trackSlugs([
            ...(is_array($suggestions['unlocked_tracks'] ?? null) ? $suggestions['unlocked_tracks'] : []),
            ...(is_array($suggestions['bridge_tracks'] ?? null) ? $suggestions['bridge_tracks'] : []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return RoadmapPortfolioResult::empty()->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyPortfolio(): array
    {
        return self::empty();
    }

    /**
     * @param  array<string, mixed>  $signals
     * @param  list<array<string, mixed>>  $unlocked
     * @param  list<array<string, mixed>>  $bridge
     * @param  list<array<string, mixed>>  $longTerm
     * @param  list<array<string, mixed>>  $deferred
     */
    private static function addAdvancedTracks(array $signals, array &$unlocked, array &$bridge, array &$longTerm, array &$deferred): void
    {
        if ($signals['front_lever_bridge']) {
            $bridge[] = self::track(
                'front_lever',
                'Your pulling and hollow base can start lever-specific rows and tuck work.',
                ['pull_capacity', 'row_volume', 'core_bodyline', 'straight_arm_tolerance'],
                'Own tuck lever rows and 10 to 15 second tuck holds.',
                ['strict_pull_up', 'l_sit'],
            );
        } else {
            $longTerm[] = self::track(
                'front_lever',
                'Front lever should wait behind pull volume, rows, and hollow body strength.',
                ['pull_capacity', 'row_volume', 'core_bodyline'],
                'Reach pull-up capacity and strong horizontal rows first.',
                ['strict_pull_up'],
            );
        }

        if ($signals['muscle_up_bridge']) {
            $bridge[] = self::track(
                'muscle_up',
                'Pulling and dip numbers are close enough for transition prep while strength continues building.',
                ['pull_capacity', 'dip_support', 'weighted_strength'],
                'Build chest-to-bar pulls and deep dips before full attempts.',
                ['strict_pull_up', 'strict_dip'],
            );
        } else {
            $longTerm[] = self::track(
                'muscle_up',
                'Muscle-up work needs stronger pulling height and dip depth before it becomes a main target.',
                ['pull_capacity', 'dip_support', 'row_volume'],
                'Reach chest-to-bar pulling and controlled deep dips.',
                ['strict_pull_up', 'strict_dip'],
            );
        }

        if ($signals['ring_dip_bridge']) {
            $bridge[] = self::track(
                'ring_dip',
                'Support strength is close enough to introduce ring stability carefully.',
                ['dip_support', 'straight_arm_tolerance', 'push_capacity'],
                'Own stable ring support before ring dip volume.',
                ['strict_dip', 'l_sit'],
            );
        } else {
            $longTerm[] = self::track(
                'ring_dip',
                'Ring dips should wait until bar dips and support holds are stable.',
                ['dip_support', 'straight_arm_tolerance'],
                'Build bar dips and ring support first.',
                ['strict_dip'],
            );
        }

        if ($signals['planche_bridge']) {
            $bridge[] = self::track(
                'planche',
                'Your pressing base can support early planche leans and straight-arm prep.',
                ['push_capacity', 'straight_arm_tolerance', 'core_bodyline'],
                'Keep this as bridge work until wrists and scapular protraction tolerate volume.',
                ['strict_push_up', 'handstand'],
            );
        } else {
            $deferred[] = self::track(
                'planche',
                'Planche is a long-term strength skill; build push-up, wrist, hollow, and support foundations first.',
                ['push_capacity', 'straight_arm_tolerance', 'core_bodyline'],
                'Reach reliable push-up volume and pain-free straight-arm loading.',
                ['strict_push_up', 'handstand'],
            );
        }

        if ($signals['weighted_pull_ready']) {
            $unlocked[] = self::track(
                'weighted_pull_up',
                'Pull-up volume is high enough to start measured added-load work.',
                ['pull_capacity', 'weighted_strength'],
                'Build repeatable sets before testing heavier loads.',
                ['strict_pull_up'],
            );
        } else {
            $longTerm[] = self::track(
                'weighted_pull_up',
                'Weighted pulling should come after consistent pull-up sets.',
                ['pull_capacity', 'weighted_strength'],
                'Reach roughly 3 sets of 8 pull-ups.',
                ['strict_pull_up'],
            );
        }

        if ($signals['one_arm_pull_ready']) {
            $bridge[] = self::track(
                'one_arm_pull_up',
                'Your weighted pulling signal can support controlled unilateral pulling prep.',
                ['weighted_strength', 'pull_capacity', 'straight_arm_tolerance'],
                'Use assisted one-arm work while preserving bilateral strength.',
                ['weighted_pull_up', 'front_lever'],
            );
        } else {
            $deferred[] = self::track(
                'one_arm_pull_up',
                'One-arm pull-up work should wait behind a much stronger weighted pull-up base.',
                ['weighted_strength', 'pull_capacity'],
                'Build toward heavy weighted pull-ups and resilient elbow tolerance first.',
                ['strict_pull_up', 'weighted_pull_up'],
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $unlocked
     * @param  list<array<string, mixed>>  $bridge
     * @param  list<array<string, mixed>>  $longTerm
     * @param  list<array<string, mixed>>  $deferred
     */
    private static function addRequestedAspirations(RoadmapInput $input, array $unlocked, array $bridge, array &$longTerm, array &$deferred): void
    {
        $activeSlugs = self::trackSlugs([...$unlocked, ...$bridge]);
        $knownSlugs = self::trackSlugs([...$unlocked, ...$bridge, ...$longTerm, ...$deferred]);
        $requested = $input->longTermAspirations;

        foreach ($requested as $skill) {
            if (! is_string($skill) || in_array($skill, $activeSlugs, true) || in_array($skill, $knownSlugs, true)) {
                continue;
            }

            if (! isset(self::TARGET_LABELS[$skill])) {
                continue;
            }

            $longTerm[] = self::track(
                $skill,
                'Keep this visible as a later roadmap while the current plan builds the required base.',
                ['core_bodyline', 'pull_capacity', 'push_capacity'],
                'Let baseline tests unlock the right bridge when readiness improves.',
                [],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function signals(RoadmapInput $input): array
    {
        $tests = $input->baselineTests;
        $pushUps = is_array($tests['push_ups'] ?? null) ? $tests['push_ups'] : [];
        $pullUps = is_array($tests['pull_ups'] ?? null) ? $tests['pull_ups'] : [];
        $dips = is_array($tests['dips'] ?? null) ? $tests['dips'] : [];
        $squat = is_array($tests['squat'] ?? null) ? $tests['squat'] : [];
        $skillStatuses = is_array($input->goalModules['skill_statuses'] ?? null) ? $input->goalModules['skill_statuses'] : [];
        $mobility = is_array($input->goalModules['mobility_checks'] ?? null) ? $input->goalModules['mobility_checks'] : [];

        $pushReps = self::intValue($pushUps['max_strict_reps'] ?? null);
        $pullReps = self::intValue($pullUps['max_strict_reps'] ?? null);
        $dipReps = self::intValue($dips['max_strict_reps'] ?? null);
        $barbellSquatReps = self::intValue($squat['barbell_reps'] ?? null);
        $barbellSquatRatio = self::barbellSquatRatio($input);
        $hollowHold = self::intValue($tests['hollow_hold_seconds'] ?? null);
        $handstandStatus = is_array($skillStatuses['handstand'] ?? null) ? $skillStatuses['handstand'] : [];
        $lSitStatus = is_array($skillStatuses['l_sit'] ?? null) ? $skillStatuses['l_sit'] : [];
        $handstandReadyByStatus = self::statusIn($handstandStatus, [
            'chest_to_wall_handstand',
            'wall_handstand_shoulder_taps',
            'freestanding_kick_up',
            'freestanding_handstand',
        ]) || self::intValue($handstandStatus['best_hold_seconds'] ?? null) >= 10;
        $lSitReadyByStatus = self::statusIn($lSitStatus, [
            'one_leg_l_sit',
            'tuck_l_sit',
            'full_l_sit',
            'v_sit_prep',
        ]) || self::intValue($lSitStatus['best_hold_seconds'] ?? null) >= 5;
        $hasBarbellSquatBase = $barbellSquatReps >= 3 && $barbellSquatRatio >= 0.75;

        $hasPushBase = $pushReps >= 1;
        $hasPullUp = $pullReps >= 1;
        $hasPullBase = $hasPullUp;
        $hasDip = $dipReps >= 1;
        $hasDipBase = $hasDip || $pushReps >= 10;
        $wristBlocked = in_array($mobility['wrist_extension'] ?? 'not_tested', ['blocked', 'painful'], true);
        $shoulderBlocked = in_array($mobility['shoulder_flexion'] ?? 'not_tested', ['blocked', 'painful'], true);
        $ankleBlocked = in_array($mobility['ankle_dorsiflexion'] ?? 'not_tested', ['blocked', 'painful'], true);
        $weightedPullRatio = self::weightedPullRatio($input);

        return [
            'push_reps' => $pushReps,
            'pull_reps' => $pullReps,
            'dip_reps' => $dipReps,
            'hollow_hold' => $hollowHold,
            'has_push_base' => $hasPushBase,
            'has_pull_base' => $hasPullBase,
            'has_pull_up' => $hasPullUp,
            'has_dip_base' => $hasDipBase,
            'has_dip' => $hasDip,
            'handstand_ready' => $hasPushBase && $hollowHold >= 20 && $handstandReadyByStatus && ! $wristBlocked && ! $shoulderBlocked,
            'l_sit_ready' => $hasDipBase && ($hollowHold >= 20 || $lSitReadyByStatus),
            'pistol_ready' => $hasBarbellSquatBase && ! $ankleBlocked,
            'front_lever_bridge' => $hasPullBase && $pullReps >= 3 && $hollowHold >= 20,
            'muscle_up_bridge' => $pullReps >= 3 && $dipReps >= 3,
            'ring_dip_bridge' => $hasDip && $dipReps >= 3,
            'planche_bridge' => $pushReps >= 15 && $hollowHold >= 25 && ! $wristBlocked,
            'weighted_pull_ready' => $pullReps >= 8,
            'one_arm_pull_ready' => $weightedPullRatio >= 0.45,
        ];
    }

    /**
     * @param  array<string, mixed>  $signals
     */
    private static function level(array $signals): string
    {
        if (($signals['pull_reps'] ?? 0) >= 8 && ($signals['dip_reps'] ?? 0) >= 8 && ($signals['push_reps'] ?? 0) >= 25) {
            return 'advanced';
        }

        if (($signals['push_reps'] ?? 0) >= 10 && ($signals['pull_reps'] ?? 0) >= 1 && ($signals['hollow_hold'] ?? 0) >= 20) {
            return 'intermediate';
        }

        if (($signals['has_push_base'] ?? false) && ($signals['has_pull_base'] ?? false)) {
            return 'beginner';
        }

        return 'foundation';
    }

    /**
     * @param  array<string, mixed>  $signals
     */
    private static function summary(array $signals): string
    {
        return match (self::level($signals)) {
            'advanced' => 'You can handle focused skill blocks, but advanced targets still need one main priority.',
            'intermediate' => 'You have enough base strength for a focused skill roadmap plus one light secondary exposure.',
            'beginner' => 'The best path is a tight bridge from current progressions into clean foundational skills.',
            default => 'Build the first reliable push, pull, squat, and bodyline signals before advanced skills become main targets.',
        };
    }

    /**
     * @return array{notes: list<string>}
     */
    private static function bodyContext(RoadmapInput $input): array
    {
        $notes = [];
        $age = self::intValue($input->profileContext['age_years'] ?? null);
        $trainingAge = self::intValue($input->trainingContext['training_age_months'] ?? null);
        $bodyweight = self::numberValue($input->profileContext['current_bodyweight_value'] ?? null);
        $height = self::numberValue($input->profileContext['height_value'] ?? null);
        $heightUnit = $input->profileContext['height_unit'] ?? 'cm';
        $bodyweightUnit = $input->profileContext['bodyweight_unit'] ?? 'kg';

        if ($age >= 40) {
            $notes[] = 'Use a slightly longer ramp-up and keep recovery checks in the first blocks.';
        }

        if ($trainingAge > 0 && $trainingAge < 6) {
            $notes[] = 'Treat the first block as foundation building even if the long-term skills are advanced.';
        }

        $bmi = self::bmi($bodyweight, is_string($bodyweightUnit) ? $bodyweightUnit : 'kg', $height, is_string($heightUnit) ? $heightUnit : 'cm');
        if ($bmi !== null && $bmi >= 30.0) {
            $notes[] = 'Prioritize joint-friendly volume, pulling base, and gradual leverage before high-load unilateral targets.';
        }

        return ['notes' => $notes];
    }

    private static function weightedPullRatio(RoadmapInput $input): float
    {
        $bodyweight = self::numberValue($input->profileContext['current_bodyweight_value'] ?? null);
        if ($bodyweight <= 0.0) {
            return 0.0;
        }

        $bodyweightUnit = $input->profileContext['bodyweight_unit'] ?? 'kg';
        $weightedBaselines = is_array($input->goalModules['weighted_baselines'] ?? null) ? $input->goalModules['weighted_baselines'] : [];
        $unit = $weightedBaselines['unit'] ?? $bodyweightUnit;
        $movements = is_array($weightedBaselines['movements'] ?? null) ? $weightedBaselines['movements'] : [];

        foreach ($movements as $movement) {
            if (! is_array($movement) || ($movement['movement'] ?? null) !== 'weighted_pull_up') {
                continue;
            }

            $load = self::numberValue($movement['external_load_value'] ?? null);

            if ($load <= 0.0) {
                continue;
            }

            if ($unit !== $bodyweightUnit) {
                $load = $unit === 'lb' ? $load * 0.45359237 : $load / 0.45359237;
            }

            return $load / $bodyweight;
        }

        return 0.0;
    }

    private static function barbellSquatRatio(RoadmapInput $input): float
    {
        $bodyweight = self::numberValue($input->profileContext['current_bodyweight_value'] ?? null);
        $tests = $input->baselineTests;
        $squat = is_array($tests['squat'] ?? null) ? $tests['squat'] : [];
        $load = self::numberValue($squat['barbell_load_value'] ?? null);

        if ($bodyweight <= 0.0 || $load <= 0.0) {
            return 0.0;
        }

        return $load / $bodyweight;
    }

    private static function bmi(float $bodyweight, string $bodyweightUnit, float $height, string $heightUnit): ?float
    {
        if ($bodyweight <= 0.0 || $height <= 0.0) {
            return null;
        }

        $kg = $bodyweightUnit === 'lb' ? $bodyweight * 0.45359237 : $bodyweight;
        $meters = $heightUnit === 'in' ? $height * 0.0254 : $height / 100;

        if ($meters <= 0.0) {
            return null;
        }

        return $kg / ($meters * $meters);
    }

    /**
     * @param  list<array<string, mixed>>  $unlocked
     * @param  list<array<string, mixed>>  $bridge
     * @param  array<string, mixed>  $track
     */
    private static function placeTrack(bool $unlockedCondition, array &$unlocked, array &$bridge, array $track): void
    {
        if ($unlockedCondition) {
            $unlocked[] = $track;

            return;
        }

        $bridge[] = $track;
    }

    /**
     * @param  list<string>  $baseFocusAreas
     * @param  list<string>  $compatibleSecondarySkills
     * @return array<string, mixed>
     */
    private static function track(string $skill, string $reason, array $baseFocusAreas, string $nextGate, array $compatibleSecondarySkills): array
    {
        return [
            'skill' => $skill,
            'label' => self::TARGET_LABELS[$skill] ?? $skill,
            'reason' => $reason,
            'base_focus_areas' => $baseFocusAreas,
            'next_gate' => $nextGate,
            'compatible_secondary_skills' => $compatibleSecondarySkills,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<array<string, mixed>>
     */
    private static function dedupeTracks(array $tracks): array
    {
        $deduped = [];

        foreach ($tracks as $track) {
            $skill = $track['skill'] ?? null;

            if (! is_string($skill) || isset($deduped[$skill])) {
                continue;
            }

            $deduped[$skill] = $track;
        }

        return array_values($deduped);
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<string>
     */
    private static function trackSlugs(array $tracks): array
    {
        return self::uniqueStrings(array_filter(
            array_map(fn (array $track): mixed => $track['skill'] ?? null, $tracks),
            is_string(...),
        ));
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, is_string(...))));
    }

    /**
     * @param  array<string, mixed>  $skillStatus
     * @param  list<string>  $statuses
     */
    private static function statusIn(array $skillStatus, array $statuses): bool
    {
        $status = $skillStatus['status'] ?? null;

        return is_string($status) && in_array($status, $statuses, true);
    }

    private static function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function numberValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
