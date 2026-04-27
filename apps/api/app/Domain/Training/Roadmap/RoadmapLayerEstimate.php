<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapLayerEstimate
{
    /**
     * @param  array{label: string, eta: RoadmapEtaRange, lanes: list<string>}  $currentBlock
     * @param  array{label: string, eta: RoadmapEtaRange, lanes: list<string>}  $forecast
     * @param  array{label: string, eta: RoadmapEtaRange, lanes: list<string>}  $aspirationLayer
     * @param  list<string>  $retestCadence
     */
    public function __construct(
        public string $primarySkill,
        public RoadmapEtaRange $primaryEta,
        public array $currentBlock,
        public array $forecast,
        public array $aspirationLayer,
        public array $retestCadence,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'primary_skill' => $this->primarySkill,
            'primary_eta' => $this->primaryEta->toArray(),
            'current_block' => [
                ...$this->currentBlock,
                'eta' => $this->currentBlock['eta']->toArray(),
            ],
            'forecast' => [
                ...$this->forecast,
                'eta' => $this->forecast['eta']->toArray(),
            ],
            'aspiration_layer' => [
                ...$this->aspirationLayer,
                'eta' => $this->aspirationLayer['eta']->toArray(),
            ],
            'retest_cadence' => $this->retestCadence,
        ];
    }
}
