<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Data;

use Illuminate\Http\Request;

final readonly class ResponseContext
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public bool $success,
        public int $status,
        public ?string $message,
        public mixed $data,
        public ?ErrorBag $errors,
        public array $meta,
        public array $headers,
        public Request $request,
    ) {}
}
