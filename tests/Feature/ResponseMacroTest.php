<?php

use Illuminate\Routing\ResponseFactory as LaravelResponseFactory;
use Illuminate\Support\Facades\Response;
use Satheez\LaravelApiResponse\Exceptions\InvalidConfigurationException;
use Satheez\LaravelApiResponse\Exceptions\MacroRegistrationException;
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

it('ignores malformed runtime macro arguments', function () {
    LaravelResponseFactory::flushMacros();

    app(ResponseMacroRegistrar::class)->register();

    $response = Response::success(['id' => 1], 'Loaded', 'bad-status', 'bad-headers', 'bad-meta');

    expect($response->status())->toBe(200)
        ->and($response->headers->has('bad-headers'))->toBeFalse()
        ->and($response->getData(true))->toMatchArray([
            'data' => ['id' => 1],
            'meta' => [],
        ]);
});

it('normalizes mixed validation macro arguments', function () {
    LaravelResponseFactory::flushMacros();

    app(ResponseMacroRegistrar::class)->register();

    $response = Response::validationError([
        'email' => ['Required', 123],
        'name' => 'Required',
        0 => ['Ignored'],
    ]);

    expect($response->getData(true)['errors'])->toBe([
        'email' => ['Required'],
        'name' => 'Required',
    ]);
});

it('defaults malformed validation macro arguments to an empty error bag', function () {
    LaravelResponseFactory::flushMacros();

    app(ResponseMacroRegistrar::class)->register();

    expect(Response::validationError(new stdClass)->getData(true)['errors'])->toBe([]);
});

it('rejects malformed macro config entries', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names.success', new stdClass);

    app(ResponseMacroRegistrar::class)->register();
})->throws(InvalidConfigurationException::class, 'Configured response macro name for [success] must be a string, [stdClass] given.');

it('rejects non array macro name config', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names', 'success');

    app(ResponseMacroRegistrar::class)->register();
})->throws(InvalidConfigurationException::class, 'Configured response macro names must be an array.');

it('rejects non string macro keys', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names', [
        'success',
    ]);

    app(ResponseMacroRegistrar::class)->register();
})->throws(InvalidConfigurationException::class, 'Configured response macro key [int] must be a string.');

it('rejects empty macro names', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names.success', '');

    app(ResponseMacroRegistrar::class)->register();
})->throws(InvalidConfigurationException::class, 'Configured response macro name for [success] cannot be empty.');

it('rejects macro config keys without matching factory methods', function () {
    LaravelResponseFactory::flushMacros();
    config()->set('api-response.macros.names.missing', 'missing');

    app(ResponseMacroRegistrar::class)->register();
})->throws(MacroRegistrationException::class, 'Cannot register response macro for missing method [missing].');

it('rejects direct calls to unknown factory macro methods', function () {
    app(ResponseMacroRegistrar::class)->callFactory('missing', []);
})->throws(MacroRegistrationException::class, 'Cannot register response macro for missing method [missing].');
