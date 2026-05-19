<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Stringable;
use Throwable;

final readonly class ErrorNormalizer
{
    /**
     * @return array<string, mixed>|null
     */
    public function normalize(mixed $errors): ?array
    {
        if ($errors === null) {
            return null;
        }

        if ($errors instanceof ValidationException) {
            return $this->normalizeArray($errors->errors());
        }

        if ($errors instanceof MessageBag) {
            return $this->normalizeArray($errors->getMessages());
        }

        if ($errors instanceof Arrayable) {
            return $this->normalize($errors->toArray());
        }

        if ($errors instanceof Throwable) {
            return ['exception' => [$errors->getMessage()]];
        }

        if (is_string($errors)) {
            return ['message' => [$errors]];
        }

        if (is_array($errors)) {
            return $this->normalizeArray($errors);
        }

        if (is_scalar($errors) || $errors instanceof Stringable) {
            return ['message' => [(string) $errors]];
        }

        return ['message' => ['Invalid error payload.']];
    }

    /**
     * @param  array<mixed, mixed>  $errors
     * @return array<string, mixed>
     */
    private function normalizeArray(array $errors): array
    {
        $normalized = [];

        foreach ($errors as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }
}
