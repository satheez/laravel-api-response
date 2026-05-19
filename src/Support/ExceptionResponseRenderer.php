<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Satheez\LaravelApiResponse\ResponseFactory;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final readonly class ExceptionResponseRenderer
{
    public function __construct(
        private ResponseFactory $responses,
        private MessageResolver $messages,
    ) {}

    public function render(Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return $this->responses->validationError($exception);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->responses->unauthorized($this->messageOrNull($exception));
        }

        if ($exception instanceof AuthorizationException) {
            return $this->responses->forbidden($this->messageOrNull($exception));
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->responses->notFound();
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->responses->notFound($this->messageOrNull($exception));
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $this->responses->error(
                $this->messageOrNull($exception) ?? $this->defaultMessageForStatus($exception->getStatusCode()),
                $exception->getStatusCode(),
            );
        }

        return $this->responses->error(
            $this->fallbackMessage($exception),
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    private function messageOrNull(Throwable $exception): ?string
    {
        return $exception->getMessage() === '' ? null : $exception->getMessage();
    }

    private function defaultMessageForStatus(int $status): string
    {
        return match ($status) {
            Response::HTTP_UNAUTHORIZED => $this->messages->fromConfig('errors.unauthorized'),
            Response::HTTP_FORBIDDEN => $this->messages->fromConfig('errors.forbidden'),
            Response::HTTP_NOT_FOUND => $this->messages->fromConfig('errors.not_found'),
            Response::HTTP_BAD_REQUEST => $this->messages->fromConfig('errors.invalid_request'),
            Response::HTTP_TOO_MANY_REQUESTS => $this->messages->fromConfig('errors.too_many_requests'),
            default => $this->messages->fromConfig('errors.server_error'),
        };
    }

    private function fallbackMessage(Throwable $exception): string
    {
        if ((bool) config('app.debug', false) || (bool) config('api-response.exceptions.expose_messages', false)) {
            return $exception->getMessage();
        }

        return $this->messages->fromConfig('errors.server_error');
    }
}
