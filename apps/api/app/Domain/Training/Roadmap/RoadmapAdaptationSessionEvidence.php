<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapAdaptationSessionEvidence
{
    /**
     * @param  list<RoadmapAdaptationModuleEvidence>  $modules
     */
    public function __construct(
        public int $weekNumber,
        public ?int $painLevel,
        public ?float $readinessScore,
        public array $modules,
    ) {}

    /**
     * @param  array<string, mixed>  $value
     */
    public static function fromArray(array $value): self
    {
        return new self(
            weekNumber: max(1, self::intValue($value['week_number'] ?? null)),
            painLevel: self::nullableInt($value['pain_level'] ?? null),
            readinessScore: self::nullableFloat($value['readiness_score'] ?? null),
            modules: array_values(array_map(
                static fn (array $module): RoadmapAdaptationModuleEvidence => RoadmapAdaptationModuleEvidence::fromArray($module),
                array_filter(is_array($value['modules'] ?? null) ? $value['modules'] : [], is_array(...)),
            )),
        );
    }

    private static function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
