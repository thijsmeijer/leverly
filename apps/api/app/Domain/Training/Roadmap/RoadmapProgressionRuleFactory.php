<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapProgressionRuleFactory
{
    /**
     * @param  array<string, mixed>  $module
     * @return array<string, mixed>
     */
    public static function fromModule(array $module, RoadmapInput $input): array
    {
        $ruleType = self::ruleType($module);
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $progressionAllowed = $painLevel === null || $painLevel < 4;

        return [
            'module_id' => self::stringValue($module['module_id'] ?? null, 'module'),
            'skill_track_id' => self::stringValue($module['skill_track_id'] ?? null, ''),
            'title' => self::stringValue($module['title'] ?? null, 'Training module'),
            'rule_type' => $ruleType,
            'metric' => self::metric($module),
            'progression_allowed' => $progressionAllowed,
            'next_action' => $progressionAllowed ? 'progress_when_ready' : 'maintain_or_regress',
            'success_requirements' => self::successRequirements($ruleType),
            'allowed_levers' => self::allowedLevers($ruleType),
            'only_one_major_lever' => true,
            'pain_rule' => 'Do not progress when pain reaches 4/10 or changes technique.',
            'next_adjustment' => $progressionAllowed
                ? self::nextAdjustment($ruleType)
                : 'Hold the current dose, reduce range or assistance demand, and rebuild pain-free quality.',
            'deload_triggers' => self::deloadTriggers($ruleType, $painLevel),
        ];
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function ruleType(array $module): string
    {
        $purpose = self::stringValue($module['purpose'] ?? null, '');
        $metric = self::metric($module);
        $skill = self::stringValue($module['skill_track_id'] ?? null, '');

        if ($metric === 'load' || str_starts_with($skill, 'weighted_')) {
            return 'weighted_load';
        }

        if ($metric === 'hold_seconds') {
            return 'static_hold';
        }

        if ($purpose === 'technical_practice' || $metric === 'quality') {
            return 'technical_practice';
        }

        if (in_array($purpose, ['mobility_prep', 'tissue_capacity'], true)) {
            return 'mobility_tissue';
        }

        if ($purpose === 'maintenance') {
            return 'maintenance';
        }

        return 'dynamic_reps';
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function metric(array $module): string
    {
        $dose = is_array($module['dose'] ?? null) ? $module['dose'] : [];

        return self::stringValue($dose['metric'] ?? null, 'reps');
    }

    /**
     * @return list<string>
     */
    private static function successRequirements(string $ruleType): array
    {
        return match ($ruleType) {
            'static_hold' => ['total_quality_time', 'bodyline_quality', 'repeated_success', 'pain_below_4'],
            'technical_practice' => ['clean_attempts', 'consistency', 'confidence', 'pain_below_4'],
            'weighted_load' => ['total_system_load_tracked', 'relative_strength_stable', 'two_successful_exposures', 'pain_below_4'],
            'mobility_tissue' => ['low_irritation_range', 'repeatable_position', 'pain_below_4'],
            'maintenance' => ['no_regression', 'low_fatigue', 'pain_below_4'],
            default => ['two_successful_exposures', 'form_quality_clear', 'rir_or_rpe_in_target', 'pain_below_4'],
        };
    }

    /**
     * @return list<string>
     */
    private static function allowedLevers(string $ruleType): array
    {
        return match ($ruleType) {
            'static_hold' => ['total_quality_seconds', 'lever_length', 'set_count'],
            'technical_practice' => ['attempt_count', 'hold_quality', 'balance_context'],
            'weighted_load' => ['total_system_load', 'reps', 'set_count'],
            'mobility_tissue' => ['range', 'duration', 'frequency'],
            'maintenance' => ['frequency', 'dose'],
            default => ['reps', 'range_of_motion', 'tempo', 'assistance'],
        };
    }

    private static function nextAdjustment(string $ruleType): string
    {
        return match ($ruleType) {
            'static_hold' => 'Add 3-5 seconds of total quality time before changing leverage.',
            'technical_practice' => 'Add a small number of clean attempts or longer controlled exposure before increasing difficulty.',
            'weighted_load' => 'Add a microload or 2-5% total system load after repeatable submaximal exposures.',
            'mobility_tissue' => 'Add range or duration only while the position stays low-irritation.',
            'maintenance' => 'Keep the dose stable unless quality or readiness drops.',
            default => 'Use double progression: add reps first, then reduce assistance or increase range once quality repeats.',
        };
    }

    /**
     * @return list<string>
     */
    private static function deloadTriggers(string $ruleType, ?int $painLevel): array
    {
        $triggers = [
            'Deload when performance drops for two exposures.',
            'Deload when form quality breaks before the planned dose.',
        ];

        if ($painLevel !== null && $painLevel >= 4) {
            $triggers[] = 'Pain at 4/10 or higher blocks progression until the next pain-free exposure.';
        } else {
            $triggers[] = 'Pain at 4/10 or higher triggers a regression or deload.';
        }

        if ($ruleType === 'weighted_load') {
            $triggers[] = 'Deload when total system load feels maximal or bar speed/control drops.';
        }

        if ($ruleType === 'static_hold') {
            $triggers[] = 'Deload when holds lose bodyline or shake before target time.';
        }

        return $triggers;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }
}
