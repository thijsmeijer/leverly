<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class ProgressionGraphNode
{
    public function __construct(
        public string $slug,
        public string $nodeId,
        public string $label,
        public int $order,
        public string $family,
        public string $skillTrackId,
        public string $type,
        public string $movementFamily,
        public string $metricType,
        public string $measurementRule,
        public string $fatigueClass,
        public string $tendonClass,
        /** @var list<string> */
        public array $requiredEquipment,
        /** @var list<string> */
        public array $environmentCapabilities,
        /** @var list<string> */
        public array $contraindicatedPainKeys,
        /** @var list<string> */
        public array $mobilityRequirements,
        /** @var list<string> */
        public array $primaryDomains,
        /** @var array<string, int> */
        public array $stressVector,
        public string $evidenceGrade,
        public bool $schedulable,
        public int $minEdgeWeeks,
        public int $maxEdgeWeeks,
        public string $unlock,
    ) {}

    /**
     * @return array{min_weeks: int, max_weeks: int}
     */
    public function edgeTimeBand(): array
    {
        return [
            'min_weeks' => $this->minEdgeWeeks,
            'max_weeks' => $this->maxEdgeWeeks,
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     node_id: string,
     *     label: string,
     *     order: int,
     *     family: string,
     *     skill_track_id: string,
     *     type: string,
     *     movement_family: string,
     *     metric_type: string,
     *     measurement_rule: string,
     *     fatigue_class: string,
     *     tendon_class: string,
     *     required_equipment: list<string>,
     *     environment_capabilities: list<string>,
     *     contraindicated_pain_keys: list<string>,
     *     mobility_requirements: list<string>,
     *     primary_domains: list<string>,
     *     stress_vector: array<string, int>,
     *     evidence_grade: string,
     *     schedulable: bool,
     *     edge_time_band: array{min_weeks: int, max_weeks: int},
     *     unlock: string
     * }
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'node_id' => $this->nodeId,
            'label' => $this->label,
            'order' => $this->order,
            'family' => $this->family,
            'skill_track_id' => $this->skillTrackId,
            'type' => $this->type,
            'movement_family' => $this->movementFamily,
            'metric_type' => $this->metricType,
            'measurement_rule' => $this->measurementRule,
            'fatigue_class' => $this->fatigueClass,
            'tendon_class' => $this->tendonClass,
            'required_equipment' => $this->requiredEquipment,
            'environment_capabilities' => $this->environmentCapabilities,
            'contraindicated_pain_keys' => $this->contraindicatedPainKeys,
            'mobility_requirements' => $this->mobilityRequirements,
            'primary_domains' => $this->primaryDomains,
            'stress_vector' => $this->stressVector,
            'evidence_grade' => $this->evidenceGrade,
            'schedulable' => $this->schedulable,
            'edge_time_band' => $this->edgeTimeBand(),
            'unlock' => $this->unlock,
        ];
    }
}
