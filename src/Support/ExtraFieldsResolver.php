<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Contracts\Container\Container;
use Satheez\LaravelApiResponse\Contracts\ExtraFieldResolver;
use Satheez\LaravelApiResponse\Data\ResponseContext;
use Satheez\LaravelApiResponse\Exceptions\InvalidConfigurationException;
use Satheez\LaravelApiResponse\Exceptions\InvalidResolverException;

final readonly class ExtraFieldsResolver
{
    private const RESERVED_FIELDS = [
        'success',
        'message',
        'data',
        'errors',
        'meta',
    ];

    public function __construct(
        private Container $container,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(ResponseContext $context): array
    {
        $fields = $this->staticExtraFields();

        foreach ($this->resolverClasses() as $resolverClass) {
            $resolver = $this->container->make($resolverClass);

            if (! $resolver instanceof ExtraFieldResolver) {
                throw InvalidResolverException::resolverMustImplement($resolverClass);
            }

            foreach ($resolver->resolve($context) as $key => $value) {
                if (! is_string($key)) {
                    throw InvalidConfigurationException::resolvedExtraFieldKeyMustBeString($resolverClass, $key);
                }

                $this->ensureAllowedField($key);
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private function staticExtraFields(): array
    {
        $configuredFields = config('api-response.extra_fields', []);

        if (! is_array($configuredFields)) {
            throw InvalidConfigurationException::extraFieldsMustBeArray();
        }

        $fields = [];

        foreach ($configuredFields as $key => $value) {
            if (! is_string($key)) {
                throw InvalidConfigurationException::extraFieldKeyMustBeString($key);
            }

            $this->ensureAllowedField($key);
            $this->ensureCacheableValue($key, $value);
            $fields[$key] = $value;
        }

        return $fields;
    }

    /**
     * @return list<class-string>
     */
    private function resolverClasses(): array
    {
        $resolvers = config('api-response.extra_field_resolvers', []);

        if (! is_array($resolvers)) {
            throw InvalidConfigurationException::resolversMustBeArray();
        }

        $classes = [];

        foreach ($resolvers as $resolver) {
            if (! is_string($resolver)) {
                throw InvalidResolverException::resolverMustBeClassString($resolver);
            }

            if (! class_exists($resolver)) {
                throw InvalidResolverException::resolverClassDoesNotExist($resolver);
            }

            $classes[] = $resolver;
        }

        return $classes;
    }

    private function ensureAllowedField(string $field): void
    {
        if (in_array($field, self::RESERVED_FIELDS, true)) {
            throw InvalidConfigurationException::reservedResponseField($field);
        }
    }

    private function ensureCacheableValue(string $field, mixed $value): void
    {
        if (is_null($value) || is_scalar($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $this->ensureCacheableValue($field, $nestedValue);
            }

            return;
        }

        throw InvalidConfigurationException::extraFieldValueMustBeCacheable($field);
    }
}
