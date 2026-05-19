<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Satheez\LaravelApiResponse\Support\DataNormalizer;
use Satheez\LaravelApiResponse\Support\MessageResolver;
use Satheez\LaravelApiResponse\Support\PayloadFactory;
use Satheez\LaravelApiResponse\Support\ResponseBuilder;
use Throwable;

final readonly class ResponseFactory
{
    public function __construct(
        private PayloadFactory $payloadFactory,
        private DataNormalizer $dataNormalizer,
        private MessageResolver $messageResolver,
    ) {}

    public function builder(): ResponseBuilder
    {
        return new ResponseBuilder($this);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function success(
        mixed $data = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->respond(
            success: true,
            status: $status,
            message: $message ?? $this->messageResolver->fromConfig('success.default'),
            data: $data,
            errors: null,
            headers: $headers,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function created(
        mixed $data = null,
        ?string $message = null,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->success($data, $message ?? $this->messageResolver->fromConfig('success.created'), Response::HTTP_CREATED, $headers, $meta);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function updated(
        mixed $data = null,
        ?string $message = null,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->success($data, $message ?? $this->messageResolver->fromConfig('success.updated'), Response::HTTP_OK, $headers, $meta);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function stored(
        mixed $data = null,
        ?string $message = null,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->updated($data, $message, $headers, $meta);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function deleted(
        mixed $data = null,
        ?string $message = null,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->success($data, $message ?? $this->messageResolver->fromConfig('success.deleted'), Response::HTTP_OK, $headers, $meta);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function error(
        string $message,
        int $status = Response::HTTP_UNPROCESSABLE_ENTITY,
        mixed $errors = null,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->respond(
            success: false,
            status: $status,
            message: $message,
            data: null,
            errors: $errors,
            headers: $headers,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, string|array<int, string>>|string|MessageBag|ValidationException  $errors
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function validationError(
        array|string|MessageBag|ValidationException $errors,
        ?string $message = null,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        if (is_string($errors)) {
            return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY, null, $headers, $meta);
        }

        return $this->error(
            $message ?? $this->messageResolver->fromConfig('errors.validation'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $errors,
            $headers,
            $meta,
        );
    }

    public function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error($message ?? $this->messageResolver->fromConfig('errors.unauthorized'), Response::HTTP_UNAUTHORIZED);
    }

    public function forbidden(?string $message = null): JsonResponse
    {
        return $this->error($message ?? $this->messageResolver->fromConfig('errors.forbidden'), Response::HTTP_FORBIDDEN);
    }

    public function accessDenied(?string $message = null): JsonResponse
    {
        return $this->forbidden($message);
    }

    public function notFound(?string $message = null): JsonResponse
    {
        return $this->error($message ?? $this->messageResolver->fromConfig('errors.not_found'), Response::HTTP_NOT_FOUND);
    }

    public function invalidRequest(?string $message = null): JsonResponse
    {
        return $this->error($message ?? $this->messageResolver->fromConfig('errors.invalid_request'), Response::HTTP_BAD_REQUEST);
    }

    public function somethingWentWrong(?string $message = null): JsonResponse
    {
        return $this->error($message ?? $this->messageResolver->fromConfig('errors.server_error'), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function exception(Throwable $exception, ?string $message = null, ?int $status = null): JsonResponse
    {
        return $this->error(
            $message ?? $exception->getMessage(),
            $status ?? $this->validStatusCode($exception->getCode()),
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function resource(
        mixed $resource,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        return $this->success($resource, $message, $status, $headers, $meta);
    }

    /**
     * @param  iterable<mixed>|ResourceCollection  $collection
     * @param  class-string<JsonResource>|null  $resourceClass
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function collection(
        iterable|ResourceCollection $collection,
        ?string $resourceClass = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        if ($collection instanceof ResourceCollection) {
            return $this->success($collection, $message, $status, $headers, $meta);
        }

        $data = $resourceClass === null
            ? $this->dataNormalizer->collection($collection)
            : $resourceClass::collection($collection)->resolve(request());

        return $this->success($data, $message, $status, $headers, $meta);
    }

    /**
     * @param  AbstractPaginator<int|string, mixed>  $paginator
     * @param  class-string<JsonResource>|null  $resourceClass
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    public function paginated(
        AbstractPaginator $paginator,
        ?string $resourceClass = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $meta = [],
    ): JsonResponse {
        $items = $paginator->getCollection();
        $data = $resourceClass === null
            ? $this->dataNormalizer->collection($items)
            : $resourceClass::collection($items)->resolve(request());

        return $this->success(
            $data,
            $message,
            $status,
            $headers,
            array_merge($meta, ['pagination' => $this->paginationMeta($paginator)]),
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $meta
     */
    private function respond(
        bool $success,
        int $status,
        ?string $message,
        mixed $data,
        mixed $errors,
        array $headers,
        array $meta,
    ): JsonResponse {
        return response()->json(
            $this->payloadFactory->make($success, $status, $message, $data, $errors, $meta, $headers)->toArray(),
            $status,
            $headers,
        );
    }

    /**
     * @param  AbstractPaginator<int|string, mixed>  $paginator
     * @return array<string, mixed>
     */
    private function paginationMeta(AbstractPaginator $paginator): array
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'path' => $paginator->path(),
            'first_page_url' => $paginator->url(1),
            'next_page_url' => is_callable([$paginator, 'nextPageUrl'])
                ? call_user_func([$paginator, 'nextPageUrl'])
                : null,
            'prev_page_url' => $paginator->previousPageUrl(),
        ];

        if (method_exists($paginator, 'total')) {
            $meta['total'] = $paginator->total();
        }

        if (is_callable([$paginator, 'lastPage'])) {
            $lastPage = call_user_func([$paginator, 'lastPage']);

            if (is_int($lastPage)) {
                $meta['last_page'] = $lastPage;
                $meta['last_page_url'] = $paginator->url($lastPage);
            }
        }

        return $meta;
    }

    private function validStatusCode(int $code): int
    {
        if ($code >= 100 && $code <= 599) {
            return $code;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
