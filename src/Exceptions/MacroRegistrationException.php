<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Exceptions;

use RuntimeException;

final class MacroRegistrationException extends RuntimeException
{
    public static function missingFactoryMethod(string $method): self
    {
        return new self("Cannot register response macro for missing method [{$method}].");
    }
}
