<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Exceptions;

use InvalidArgumentException;
use Satheez\LaravelApiResponse\Contracts\ExtraFieldResolver;

final class InvalidResolverException extends InvalidArgumentException
{
    public static function resolverMustBeClassString(mixed $resolver): self
    {
        return new self(sprintf(
            'Configured extra field resolver [%s] must be a class-string.',
            get_debug_type($resolver),
        ));
    }

    public static function resolverClassDoesNotExist(string $resolverClass): self
    {
        return new self("Extra field resolver [{$resolverClass}] must be an existing class name.");
    }

    public static function resolverMustImplement(string $resolverClass): self
    {
        return new self(sprintf(
            'Extra field resolver [%s] must implement [%s].',
            $resolverClass,
            ExtraFieldResolver::class,
        ));
    }
}
