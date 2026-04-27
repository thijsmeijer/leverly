<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class DomainScore
{
    /**
     * @param  list<string>  $contributingInputs
     * @param  list<string>  $missingInputs
     * @param  list<string>  $modifiers
     * @param  list<string>  $weakLinks
     */
    public function __construct(
        public string $domain,
        public string $label,
        public int $score,
        public float $confidence,
        public float $uncertainty,
        public array $contributingInputs,
        public array $missingInputs,
        public string $bottleneck,
        public array $modifiers,
        public string $kind = 'internal',
        public string $displayDomain = '',
        public array $weakLinks = [],
    ) {}

    /**
     * @return array{
     *     domain: string,
     *     kind: string,
     *     display_domain: string,
     *     label: string,
     *     score: int,
     *     confidence: float,
     *     uncertainty: float,
     *     contributing_inputs: list<string>,
     *     missing_inputs: list<string>,
     *     bottleneck: string,
     *     modifiers: list<string>,
     *     weak_links: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'kind' => $this->kind,
            'display_domain' => $this->displayDomain,
            'label' => $this->label,
            'score' => $this->score,
            'confidence' => $this->confidence,
            'uncertainty' => $this->uncertainty,
            'contributing_inputs' => $this->contributingInputs,
            'missing_inputs' => $this->missingInputs,
            'bottleneck' => $this->bottleneck,
            'modifiers' => $this->modifiers,
            'weak_links' => $this->weakLinks,
        ];
    }
}
