<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Onboarding\Support\AthleteOnboardingOptions;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use App\Models\AthleteOnboarding;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AthleteOnboarding
 */
class AthleteOnboardingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = AthleteOnboardingOptions::recordData($this->resource);
        $data['roadmap_suggestions'] = $request->boolean('include_roadmap_intermediate')
            ? CalisthenicsRoadmapSuggester::portfolioFromAthleteData($data, includeIntermediate: true)
            : CalisthenicsRoadmapSuggester::portfolioFromAthleteData($data);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            ...$data,
            'is_complete' => $this->completed_at !== null,
            'completed_at' => $this->completed_at?->toJSON(),
            'missing_sections' => AthleteOnboardingOptions::missingSections($data),
        ];
    }
}
