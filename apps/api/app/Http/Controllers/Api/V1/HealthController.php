<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseField;
use Knuckles\Scribe\Attributes\Unauthenticated;

class HealthController extends Controller
{
    #[Group('System')]
    #[Endpoint('Get API health metadata', 'Returns a small safe payload for environment smoke checks.')]
    #[Unauthenticated]
    #[Response([
        'status' => 'ok',
        'meta' => [
            'api_version' => 'v1',
            'timestamp' => '2026-04-24T00:00:00+00:00',
        ],
    ])]
    #[ResponseField('status', 'string', 'Health state.', required: true, example: 'ok', enum: ['ok'])]
    #[ResponseField('meta', 'object', 'Metadata for this health response.', required: true)]
    #[ResponseField('meta.api_version', 'string', 'Versioned API namespace.', required: true, example: 'v1', enum: ['v1'])]
    #[ResponseField('meta.timestamp', 'string', 'ISO-8601 server timestamp.', required: true, example: '2026-04-24T00:00:00+00:00')]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'meta' => [
                'api_version' => 'v1',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
