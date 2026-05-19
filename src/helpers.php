<?php

declare(strict_types=1);

use Satheez\LaravelApiResponse\ResponseFactory;

if (! function_exists('api_response')) {
    function api_response(): ResponseFactory
    {
        return app(ResponseFactory::class);
    }
}

if (! function_exists('api')) {
    function api(): ResponseFactory
    {
        return api_response();
    }
}
