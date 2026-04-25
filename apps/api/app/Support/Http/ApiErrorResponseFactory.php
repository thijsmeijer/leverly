<?php

declare(strict_types=1);

namespace App\Support\Http;

use App\Support\Correlation\CorrelationStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiErrorResponseFactory
{
    public function __construct(
        private readonly CorrelationStore $correlationStore
    ) {}

    public function make(Throwable $exception, Request $request): JsonResponse
    {
        $status = $this->statusCode($exception);

        $payload = [
            'message' => $this->message($exception, $status),
            'meta' => $this->correlationStore->responseMetaForRequest($request),
        ];

        if ($exception instanceof ValidationException) {
            $payload['errors'] = $exception->errors();
        }

        return response()
            ->json($payload, $status)
            ->withHeaders([
                CorrelationStore::HEADER_CORRELATION_ID => $this->correlationStore->correlationIdForRequest($request),
            ]);
    }

    private function statusCode(Throwable $exception): int
    {
        if ($exception instanceof ValidationException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($exception instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($exception instanceof AuthorizationException) {
            return $exception->status() ?? Response::HTTP_FORBIDDEN;
        }

        if ($exception instanceof ModelNotFoundException) {
            return Response::HTTP_NOT_FOUND;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function message(Throwable $exception, int $status): string
    {
        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            return 'Server error.';
        }

        if ($exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        $message = $exception->getMessage();

        if ($message !== '') {
            return $message;
        }

        return Response::$statusTexts[$status] ?? 'Request failed.';
    }
}
