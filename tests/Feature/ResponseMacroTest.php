<?php

use Illuminate\Routing\ResponseFactory as LaravelResponseFactory;
use Illuminate\Support\Facades\Response;
use Satheez\LaravelApiResponse\Exceptions\InvalidConfigurationException;
use Satheez\LaravelApiResponse\Support\ResponseMacroRegistrar;

afterEach(function () {
    LaravelResponseFactory::flushMacros();
});

it('adds response facade success macros', function () {
    $response = Response::success(['id' => 1], 'Loaded');

    expect($response->getData(true))->toMatchArray([
        'success' => true,
        'message' => 'Loaded',
        'data' => ['id' => 1],
    ]);
});

it('adds response helper error macros', function () {
    $response = response()->error('Invalid request');

    expect($response->status())->toBe(422)
        ->and($response->getData(true))->toMatchArray([
            'success' => false,
            'message' => 'Invalid request',
        ]);
});

it('passes status headers and meta through success macros', function () {
    $response = response()->success(
        ['id' => 1],
        'Loaded',
        202,
        ['X-Test' => 'yes'],
        ['page' => 1],
    );

    expect($response->status())->toBe(202)
        ->and($response->headers->get('X-Test'))->toBe('yes')
        ->and($response->getData(true))->toMatchArray([
            'success' => true,
            'message' => 'Loaded',
            'data' => ['id' => 1],
            'meta' => ['page' => 1],
        ]);
});

it('adds named lifecycle and validation macros', function () {
    expect(Response::created(['id' => 1])->status())->toBe(201)
        ->and(Response::updated(['id' => 1])->getData(true)['message'])->toBe('Resource updated successfully.')
        ->and(Response::deleted()->getData(true)['message'])->toBe('Resource deleted successfully.')
        ->and(response()->validationError(['email' => ['Required']])->status())->toBe(422);
});

it('does not register macros when disabled', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.enabled', false);

    app(ResponseMacroRegistrar::class)->register();

    expect(LaravelResponseFactory::hasMacro('success'))->toBeFalse();
});

it('does not replace existing macros by default', function () {
    LaravelResponseFactory::flushMacros();
    LaravelResponseFactory::macro('success', fn () => response()->json(['custom' => true]));

    app(ResponseMacroRegistrar::class)->register();

    expect(Response::success()->getData(true))->toBe(['custom' => true]);
});

it('can replace existing macros when configured', function () {
    LaravelResponseFactory::flushMacros();
    LaravelResponseFactory::macro('success', fn () => response()->json(['custom' => true]));
    config()->set('api-response.macros.replace_existing', true);

    app(ResponseMacroRegistrar::class)->register();

    expect(Response::success()->getData(true))->toMatchArray([
        'success' => true,
        'message' => 'Request completed successfully.',
    ]);
});

it('supports custom macro names', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names.success', 'apiSuccess');

    app(ResponseMacroRegistrar::class)->register();

    expect(Response::apiSuccess(['id' => 1])->getData(true))->toMatchArray([
        'success' => true,
        'data' => ['id' => 1],
    ]);
});

it('rejects malformed macro config entries', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names.success', new stdClass);

    app(ResponseMacroRegistrar::class)->register();
})->throws(InvalidConfigurationException::class, 'Configured response macro name for [success] must be a string, [stdClass] given.');

it('rejects empty macro names', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names.success', '');

    app(ResponseMacroRegistrar::class)->register();
})->throws(InvalidConfigurationException::class, 'Configured response macro name for [success] cannot be empty.');
