<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseField;

class MeController extends Controller
{
    #[Group('Auth')]
    #[Endpoint('Get the current user', 'Returns the signed-in athlete account for the first-party web app.')]
    #[Authenticated]
    #[Response([
        'data' => [
            'id' => '01kaw4k7q6v7m9r6rddm4xyf2p',
            'name' => 'Ada Athlete',
            'email' => 'ada@example.com',
        ],
    ])]
    #[ResponseField('data', 'object', 'Current user account.', required: true)]
    #[ResponseField('data.id', 'string', 'Stable user identifier.', required: true, example: '01kaw4k7q6v7m9r6rddm4xyf2p')]
    #[ResponseField('data.name', 'string', 'Display name used for account access.', required: true, example: 'Ada Athlete')]
    #[ResponseField('data.email', 'string', 'Account email address.', required: true, example: 'ada@example.com')]
    public function __invoke(Request $request): JsonResponse
    {
        return UserResource::make($request->user())
            ->response()
            ->setStatusCode(200);
    }
}
