<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapAdaptationModuleEvidence
{
    public function __construct(
        public string $moduleId,
        public string $skillTrackId,
        public int $plannedExposures,
        public int $completedExposures,
        public float $progressDelta,
        public ?float $formScore,
        public ?int $rir,
        public ?int $rpe,
        public ?int $painLevel,
    ) {}

    /**
     * @param  array<string, mixed>  $value
     */
    public static function fromArray(array $value): self
    {
        return new self(
            moduleId: self::stringValue($value['module_id'] ?? null),
            skillTrackId: self::stringValue($value['skill_track_id'] ?? null),
            plannedExposures: max(0, self::intValue($value['planned_exposures'] ?? null)),
            completedExposures: max(0, self::intValue($value['completed_exposures'] ?? null)),
            progressDelta: self::floatValue($value['progress_delta'] ?? null),
            formScore: self::nullableFloat($value['form_score'] ?? null),
            rir: self::nullableInt($value['rir'] ?? null),
            rpe: self::nullableInt($value['rpe'] ?? null),
            painLevel: self::nullableInt($value['pain_level'] ?? null),
        );
    }

    private static function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private static function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
