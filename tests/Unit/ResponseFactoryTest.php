<?php

use Illuminate\Http\Response;
use Satheez\LaravelApiResponse\Contracts\ExtraFieldResolver;
use Satheez\LaravelApiResponse\Data\ResponseContext;
use Satheez\LaravelApiResponse\Exceptions\InvalidConfigurationException;
use Satheez\LaravelApiResponse\Exceptions\InvalidResolverException;

it('returns the standard success envelope', function () {
    $response = api_response()->success(['id' => 1], 'Loaded');

    expect($response->status())->toBe(Response::HTTP_OK)
        ->and($response->getData(true))->toBe([
            'success' => true,
            'message' => 'Loaded',
            'data' => ['id' => 1],
            'errors' => null,
            'meta' => [],
        ]);
});

it('returns the standard error envelope', function () {
    $response = api_response()->error('Invalid payload', Response::HTTP_BAD_REQUEST, [
        'email' => ['The email field is required.'],
    ]);

    expect($response->status())->toBe(Response::HTTP_BAD_REQUEST)
        ->and($response->getData(true))->toBe([
            'success' => false,
            'message' => 'Invalid payload',
            'data' => null,
            'errors' => [
                'email' => ['The email field is required.'],
            ],
            'meta' => [],
        ]);
});

it('supports configured static top level fields', function () {
    config()->set('api-response.extra_fields', [
        'api_version' => 'v1',
        'service' => 'public-api',
    ]);

    $response = api_response()->success();

    expect($response->getData(true))->toBe([
        'success' => true,
        'message' => 'Request completed successfully.',
        'data' => null,
        'errors' => null,
        'meta' => [],
        'api_version' => 'v1',
        'service' => 'public-api',
    ]);
});

it('supports configured resolver top level fields', function () {
    config()->set('api-response.extra_field_resolvers', [
        TestRequestIdResolver::class,
    ]);

    $response = api_response()->success();

    expect($response->getData(true))->toMatchArray([
        'request_id' => 'test-request-id',
    ]);
});

it('uses package translations for configured messages', function () {
    app()->setLocale('en');

    $response = api_response()->success();

    expect($response->getData(true)['message'])->toBe('Request completed successfully.');
});

it('allows literal configured messages', function () {
    config()->set('api-response.messages.success.default', 'Everything is ready.');

    $response = api_response()->success();

    expect($response->getData(true)['message'])->toBe('Everything is ready.');
});

it('prevents extra fields from overriding reserved envelope keys', function () {
    config()->set('api-response.extra_fields', [
        'success' => 'nope',
    ]);

    api_response()->success();
})->throws(InvalidConfigurationException::class, 'Reserved response field [success] cannot be overridden.');

it('prevents static extra fields from using non cacheable values', function () {
    config()->set('api-response.extra_fields', [
        'context' => new stdClass,
    ]);

    api_response()->success();
})->throws(InvalidConfigurationException::class, 'Configured extra response field [context] must be scalar, null, or an array.');

it('requires static extra field keys to be strings', function () {
    config()->set('api-response.extra_fields', [
        'api_version',
    ]);

    api_response()->success();
})->throws(InvalidConfigurationException::class, 'Configured extra response field key [int] must be a string.');

it('requires resolver extra field keys to be strings', function () {
    config()->set('api-response.extra_field_resolvers', [
        TestNumericFieldResolver::class,
    ]);

    api_response()->success();
})->throws(InvalidConfigurationException::class, sprintf(
    'Extra field resolver [%s] returned key [int], but resolver field keys must be strings.',
    TestNumericFieldResolver::class,
));

it('requires configured response messages to be strings', function () {
    config()->set('api-response.messages.success.default', ['Loaded']);

    api_response()->success();
})->throws(InvalidConfigurationException::class, 'Configured response message [success.default] must be a string.');

it('requires extra field resolvers to be existing classes', function () {
    config()->set('api-response.extra_field_resolvers', [
        'App\Support\MissingResolver',
    ]);

    api_response()->success();
})->throws(InvalidResolverException::class, 'Extra field resolver [App\Support\MissingResolver] must be an existing class name.');

it('requires extra field resolver config entries to be strings', function () {
    config()->set('api-response.extra_field_resolvers', [
        new stdClass,
    ]);

    api_response()->success();
})->throws(InvalidResolverException::class, 'Configured extra field resolver [stdClass] must be a class-string.');

it('requires extra field resolver classes to implement the contract', function () {
    config()->set('api-response.extra_field_resolvers', [
        stdClass::class,
    ]);

    api_response()->success();
})->throws(InvalidResolverException::class, sprintf(
    'Extra field resolver [stdClass] must implement [%s].',
    ExtraFieldResolver::class,
));

it('returns common success responses with configured messages', function () {
    expect(api_response()->created(['id' => 1])->status())->toBe(Response::HTTP_CREATED)
        ->and(api_response()->created()->getData(true)['message'])->toBe('Resource created successfully.')
        ->and(api_response()->updated()->status())->toBe(Response::HTTP_OK)
        ->and(api_response()->updated()->getData(true)['message'])->toBe('Resource updated successfully.')
        ->and(api_response()->deleted()->status())->toBe(Response::HTTP_OK)
        ->and(api_response()->deleted()->getData(true)['message'])->toBe('Resource deleted successfully.');
});

it('returns common error responses with configured messages', function () {
    expect(api_response()->unauthorized()->status())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and(api_response()->forbidden()->status())->toBe(Response::HTTP_FORBIDDEN)
        ->and(api_response()->notFound()->status())->toBe(Response::HTTP_NOT_FOUND)
        ->and(api_response()->invalidRequest()->status())->toBe(Response::HTTP_BAD_REQUEST)
        ->and(api_response()->somethingWentWrong()->status())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->and(api_response()->validationError(['email' => ['Required']])->status())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
});

it('supports headers and meta data on responses', function () {
    $response = api_response()->success(
        data: ['id' => 1],
        message: 'Loaded',
        status: Response::HTTP_ACCEPTED,
        headers: ['X-Test' => 'yes'],
        meta: ['page' => 1],
    );

    expect($response->status())->toBe(Response::HTTP_ACCEPTED)
        ->and($response->headers->get('X-Test'))->toBe('yes')
        ->and($response->getData(true)['meta'])->toBe(['page' => 1]);
});

class TestRequestIdResolver implements ExtraFieldResolver
{
    public function resolve(ResponseContext $context): array
    {
        return [
            'request_id' => 'test-request-id',
            'response_status' => $context->status,
        ];
    }
}

class TestNumericFieldResolver implements ExtraFieldResolver
{
    public function resolve(ResponseContext $context): array
    {
        return [
            'invalid',
        ];
    }
}
