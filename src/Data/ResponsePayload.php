<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Data;

final readonly class ResponsePayload
{
    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public bool $success,
        public ?string $message,
        public mixed $data,
        public ?array $errors,
        public array $meta,
        public array $extra = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'meta' => $this->meta,
        ], $this->extra);
    }
}
