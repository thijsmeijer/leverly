<?php

namespace App\Http\Responses\Auth;

use App\Http\Resources\Api\V1\UserResource;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return UserResource::make($request->user())
            ->response()
            ->setStatusCode(200);
    }
}
