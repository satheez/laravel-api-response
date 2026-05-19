<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Throwable;

final class ExceptionHandling
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ((bool) config('api-response.exceptions.only_json_requests', true) && ! $request->expectsJson()) {
                return null;
            }

            return app(ExceptionResponseRenderer::class)->render($exception);
        });
    }
}
