<?php

declare(strict_types=1);

namespace App\Domain\Profile\Actions;

use App\Domain\Profile\Support\AthleteProfileOptions;
use App\Models\AthleteProfile;
use App\Models\User;

final class UpsertAthleteProfileAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): AthleteProfile
    {
        $profile = AthleteProfile::query()->firstOrNew([
            'user_id' => $user->getKey(),
        ]);

        $baseData = $profile->exists
            ? AthleteProfileOptions::recordData($profile)
            : AthleteProfileOptions::defaultsFor($user);

        $profile->fill([
            ...AthleteProfileOptions::mergeProfileData(
                $baseData,
                AthleteProfileOptions::normalize($data),
            ),
            'user_id' => $user->getKey(),
        ]);

        $profile->save();

        return $profile->refresh();
    }
}
