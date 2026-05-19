<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Exceptions;

use InvalidArgumentException;

final class InvalidConfigurationException extends InvalidArgumentException
{
    public static function reservedResponseField(string $field): self
    {
        return new self("Reserved response field [{$field}] cannot be overridden.");
    }

    public static function extraFieldsMustBeArray(): self
    {
        return new self('Configured extra response fields must be an array.');
    }

    public static function extraFieldKeyMustBeString(mixed $field): self
    {
        return new self(sprintf(
            'Configured extra response field key [%s] must be a string.',
            get_debug_type($field),
        ));
    }

    public static function resolvedExtraFieldKeyMustBeString(string $resolverClass, mixed $field): self
    {
        return new self(sprintf(
            'Extra field resolver [%s] returned key [%s], but resolver field keys must be strings.',
            $resolverClass,
            get_debug_type($field),
        ));
    }

    public static function extraFieldValueMustBeCacheable(string $field): self
    {
        return new self("Configured extra response field [{$field}] must be scalar, null, or an array.");
    }

    public static function resolversMustBeArray(): self
    {
        return new self('Configured extra response field resolvers must be an array.');
    }

    public static function macrosMustBeArray(): self
    {
        return new self('Configured response macro names must be an array.');
    }

    public static function messageMustBeString(string $key): self
    {
        return new self("Configured response message [{$key}] must be a string.");
    }

    public static function macroKeyMustBeString(mixed $key): self
    {
        return new self(sprintf(
            'Configured response macro key [%s] must be a string.',
            get_debug_type($key),
        ));
    }

    public static function macroNameMustBeString(string $key, mixed $name): self
    {
        return new self(sprintf(
            'Configured response macro name for [%s] must be a string, [%s] given.',
            $key,
            get_debug_type($name),
        ));
    }

    public static function macroNameCannotBeEmpty(string $key): self
    {
        return new self("Configured response macro name for [{$key}] cannot be empty.");
    }
}
