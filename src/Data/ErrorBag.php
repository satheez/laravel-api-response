<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Data;

final readonly class ErrorBag
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        private ?array $errors,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function toArray(): ?array
    {
        return $this->errors;
    }
}
