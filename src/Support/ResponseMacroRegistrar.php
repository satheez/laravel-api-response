<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\ResponseFactory as LaravelResponseFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Satheez\LaravelApiResponse\Exceptions\InvalidConfigurationException;
use Satheez\LaravelApiResponse\Exceptions\MacroRegistrationException;
use Satheez\LaravelApiResponse\ResponseFactory;

final readonly class ResponseMacroRegistrar
{
    public function register(): void
    {
        if (! (bool) config('api-response.macros.enabled', true)) {
            return;
        }

        $replaceExisting = (bool) config('api-response.macros.replace_existing', false);
        $names = config('api-response.macros.names', []);

        if (! is_array($names)) {
            throw InvalidConfigurationException::macrosMustBeArray();
        }

        foreach ($names as $key => $macroName) {
            if (! is_string($key)) {
                throw InvalidConfigurationException::macroKeyMustBeString($key);
            }

            if (! is_string($macroName)) {
                throw InvalidConfigurationException::macroNameMustBeString($key, $macroName);
            }

            if ($macroName === '') {
                throw InvalidConfigurationException::macroNameCannotBeEmpty($key);
            }

            $method = $this->methodFor($key);

            if (! method_exists(ResponseFactory::class, $method)) {
                throw MacroRegistrationException::missingFactoryMethod($method);
            }

            if (! $replaceExisting && LaravelResponseFactory::hasMacro($macroName)) {
                continue;
            }

            $registrar = $this;

            LaravelResponseFactory::macro(
                $macroName,
                fn (...$arguments): JsonResponse => $registrar->callFactory($method, array_values($arguments)),
            );
        }
    }

    private function methodFor(string $key): string
    {
        return match ($key) {
            'validation_error' => 'validationError',
            default => $key,
        };
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function callFactory(string $method, array $arguments): JsonResponse
    {
        $factory = app(ResponseFactory::class);

        return match ($method) {
            'success' => $factory->success(
                $arguments[0] ?? null,
                $this->optionalString($arguments[1] ?? null),
                $this->integer($arguments[2] ?? 200, 200),
                $this->stringMap($arguments[3] ?? []),
                $this->arrayMap($arguments[4] ?? []),
            ),
            'error' => $factory->error(
                $this->string($arguments[0] ?? ''),
                $this->integer($arguments[1] ?? 422, 422),
                $arguments[2] ?? null,
                $this->stringMap($arguments[3] ?? []),
                $this->arrayMap($arguments[4] ?? []),
            ),
            'created' => $factory->created(
                $arguments[0] ?? null,
                $this->optionalString($arguments[1] ?? null),
                $this->stringMap($arguments[2] ?? []),
                $this->arrayMap($arguments[3] ?? []),
            ),
            'updated' => $factory->updated(
                $arguments[0] ?? null,
                $this->optionalString($arguments[1] ?? null),
                $this->stringMap($arguments[2] ?? []),
                $this->arrayMap($arguments[3] ?? []),
            ),
            'deleted' => $factory->deleted(
                $arguments[0] ?? null,
                $this->optionalString($arguments[1] ?? null),
                $this->stringMap($arguments[2] ?? []),
                $this->arrayMap($arguments[3] ?? []),
            ),
            'validationError' => $factory->validationError(
                $this->validationErrors($arguments[0] ?? []),
                $this->optionalString($arguments[1] ?? null),
                $this->stringMap($arguments[2] ?? []),
                $this->arrayMap($arguments[3] ?? []),
            ),
            default => throw MacroRegistrationException::missingFactoryMethod($method),
        };
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function optionalString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function integer(mixed $value, int $default): int
    {
        return is_int($value) ? $value : $default;
    }

    /**
     * @return array<string, string>
     */
    private function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && is_string($item)) {
                $map[$key] = $item;
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $map[$key] = $item;
            }
        }

        return $map;
    }

    /**
     * @return array<string, string|array<int, string>>|string|MessageBag|ValidationException
     */
    private function validationErrors(mixed $value): array|string|MessageBag|ValidationException
    {
        if (is_string($value) || $value instanceof MessageBag || $value instanceof ValidationException) {
            return $value;
        }

        if (! is_array($value)) {
            return [];
        }

        $errors = [];

        foreach ($value as $key => $error) {
            if (is_string($key) && is_string($error)) {
                $errors[$key] = $error;
            }

            if (is_string($key) && is_array($error)) {
                $messages = [];

                foreach ($error as $message) {
                    if (is_string($message)) {
                        $messages[] = $message;
                    }
                }

                $errors[$key] = $messages;
            }
        }

        return $errors;
    }
}
