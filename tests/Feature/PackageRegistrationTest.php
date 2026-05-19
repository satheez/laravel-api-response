<?php

use Satheez\LaravelApiResponse\Facades\ApiResponse;
use Satheez\LaravelApiResponse\LaravelApiResponseServiceProvider;
use Satheez\LaravelApiResponse\ResponseFactory;

it('registers the response factory in the container', function () {
    expect(app('api-response'))->toBeInstanceOf(ResponseFactory::class);
});

it('exposes the facade', function () {
    expect(ApiResponse::success()->getData(true)['success'])->toBeTrue();
});

it('exposes helper functions', function () {
    expect(api_response())->toBeInstanceOf(ResponseFactory::class)
        ->and(api())->toBeInstanceOf(ResponseFactory::class);
});

it('publishes package configuration', function () {
    $published = LaravelApiResponseServiceProvider::pathsToPublish(
        LaravelApiResponseServiceProvider::class,
        'api-response-config',
    );

    expect(array_key_first($published))->toEndWith('config/api-response.php')
        ->and(current($published))->toEndWith('config/api-response.php');
});

it('publishes package translations', function () {
    $published = LaravelApiResponseServiceProvider::pathsToPublish(
        LaravelApiResponseServiceProvider::class,
        'api-response-translations',
    );

    expect(array_key_first($published))->toEndWith('resources/lang')
        ->and(current($published))->toEndWith('lang/vendor/api-response');
});
