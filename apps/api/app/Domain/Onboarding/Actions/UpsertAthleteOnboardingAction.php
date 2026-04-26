<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Actions;

use App\Domain\Onboarding\Support\AthleteOnboardingOptions;
use App\Domain\Profile\Actions\UpsertAthleteProfileAction;
use App\Models\AthleteOnboarding;
use App\Models\User;

final readonly class UpsertAthleteOnboardingAction
{
    public function __construct(
        private UpsertAthleteProfileAction $upsertAthleteProfile,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data, bool $complete = false): AthleteOnboarding
    {
        $normalizedData = AthleteOnboardingOptions::normalize($data);

        $onboarding = AthleteOnboarding::query()->firstOrNew([
            'user_id' => $user->getKey(),
        ]);

        $baseData = $onboarding->exists
            ? AthleteOnboardingOptions::recordData($onboarding)
            : AthleteOnboardingOptions::defaultsFor($user);

        $onboarding->fill([
            ...AthleteOnboardingOptions::mergeDraftData($baseData, $normalizedData),
            'user_id' => $user->getKey(),
        ]);

        if ($complete) {
            $onboarding->completed_at ??= now();
        }

        $onboarding->save();
        $onboarding->refresh();

        $profileSource = $complete ? AthleteOnboardingOptions::recordData($onboarding) : $normalizedData;
        $profileData = AthleteOnboardingOptions::profileDataFor($profileSource);

        if ($profileData !== []) {
            $this->upsertAthleteProfile->execute($user, $profileData);
        }

        return $onboarding;
    }
}
