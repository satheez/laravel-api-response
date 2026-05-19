<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Contracts;

use Satheez\LaravelApiResponse\Data\ResponseContext;

interface ExtraFieldResolver
{
    /**
     * @return array<array-key, mixed>
     */
    public function resolve(ResponseContext $context): array;
}
