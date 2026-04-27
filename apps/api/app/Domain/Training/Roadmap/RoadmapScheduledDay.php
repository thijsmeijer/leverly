<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapScheduledDay
{
    /**
     * @param  list<array<string, mixed>>  $modules
     * @param  array<string, mixed>  $stressLedger
     * @param  array<string, mixed>  $timeLedger
     * @param  list<string>  $warnings
     */
    public function __construct(
        public int $dayIndex,
        public string $label,
        public string $dayType,
        public array $modules,
        public array $stressLedger,
        public array $timeLedger,
        public array $warnings,
    ) {}

    /**
     * @return array{
     *     day_index: int,
     *     label: string,
     *     day_type: string,
     *     modules: list<array<string, mixed>>,
     *     stress_ledger: array<string, mixed>,
     *     time_ledger: array<string, mixed>,
     *     warnings: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'day_index' => $this->dayIndex,
            'label' => $this->label,
            'day_type' => $this->dayType,
            'modules' => $this->modules,
            'stress_ledger' => $this->stressLedger,
            'time_ledger' => $this->timeLedger,
            'warnings' => $this->warnings,
        ];
    }
}
