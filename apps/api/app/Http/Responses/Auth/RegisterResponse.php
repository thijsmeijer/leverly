<?php

namespace App\Http\Responses\Auth;

use App\Http\Resources\Api\V1\UserResource;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        return UserResource::make($request->user())
            ->response()
            ->setStatusCode(201);
    }
}
