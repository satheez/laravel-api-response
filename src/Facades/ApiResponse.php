<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Facades;

use Illuminate\Support\Facades\Facade;
use Satheez\LaravelApiResponse\ResponseFactory;

/**
 * @method static \Satheez\LaravelApiResponse\Support\ResponseBuilder builder()
 * @method static \Illuminate\Http\JsonResponse success(mixed $data = null, ?string $message = null, int $status = 200, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse created(mixed $data = null, ?string $message = null, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse updated(mixed $data = null, ?string $message = null, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse stored(mixed $data = null, ?string $message = null, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse deleted(mixed $data = null, ?string $message = null, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse error(string $message, int $status = 422, mixed $errors = null, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse validationError(array<string, string|array<int, string>>|string|\Illuminate\Support\MessageBag|\Illuminate\Validation\ValidationException $errors, ?string $message = null, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse unauthorized(?string $message = null)
 * @method static \Illuminate\Http\JsonResponse forbidden(?string $message = null)
 * @method static \Illuminate\Http\JsonResponse accessDenied(?string $message = null)
 * @method static \Illuminate\Http\JsonResponse notFound(?string $message = null)
 * @method static \Illuminate\Http\JsonResponse invalidRequest(?string $message = null)
 * @method static \Illuminate\Http\JsonResponse somethingWentWrong(?string $message = null)
 * @method static \Illuminate\Http\JsonResponse exception(\Throwable $exception, ?string $message = null, ?int $status = null)
 * @method static \Illuminate\Http\JsonResponse resource(mixed $resource, ?string $message = null, int $status = 200, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse collection(iterable<mixed>|\Illuminate\Http\Resources\Json\ResourceCollection $collection, class-string<\Illuminate\Http\Resources\Json\JsonResource>|null $resourceClass = null, ?string $message = null, int $status = 200, array<string, string> $headers = [], array<string, mixed> $meta = [])
 * @method static \Illuminate\Http\JsonResponse paginated(\Illuminate\Pagination\AbstractPaginator<int|string, mixed> $paginator, class-string<\Illuminate\Http\Resources\Json\JsonResource>|null $resourceClass = null, ?string $message = null, int $status = 200, array<string, string> $headers = [], array<string, mixed> $meta = [])
 *
 * @see ResponseFactory
 */
final class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'api-response';
    }
}
