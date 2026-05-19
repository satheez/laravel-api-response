<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Satheez\LaravelApiResponse\Exceptions\InvalidConfigurationException;

final readonly class MessageResolver
{
    public function fromConfig(string $key): string
    {
        $message = config("api-response.messages.{$key}");

        if (! is_string($message)) {
            throw InvalidConfigurationException::messageMustBeString($key);
        }

        return $this->resolve($message) ?? '';
    }

    public function resolve(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $translated = trans($message);

        return is_string($translated) ? $translated : $message;
    }
}
