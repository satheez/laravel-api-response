<?php

declare(strict_types=1);

return [
    'messages' => [
        'success' => [
            'default' => 'api-response::messages.success.default',
            'created' => 'api-response::messages.success.created',
            'updated' => 'api-response::messages.success.updated',
            'deleted' => 'api-response::messages.success.deleted',
        ],

        'errors' => [
            'validation' => 'api-response::messages.errors.validation',
            'unauthorized' => 'api-response::messages.errors.unauthorized',
            'forbidden' => 'api-response::messages.errors.forbidden',
            'not_found' => 'api-response::messages.errors.not_found',
            'invalid_request' => 'api-response::messages.errors.invalid_request',
            'too_many_requests' => 'api-response::messages.errors.too_many_requests',
            'server_error' => 'api-response::messages.errors.server_error',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra Response Fields
    |--------------------------------------------------------------------------
    |
    | These config-cache-safe fields are added at the root of every response.
    | Values must be scalar, null, or arrays. Reserved envelope keys cannot be
    | overridden: success, message, data, errors, and meta.
    |
    */

    'extra_fields' => [
        // 'api_version' => 'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra Field Resolvers
    |--------------------------------------------------------------------------
    |
    | Resolver classes must implement ExtraFieldResolver and return an array of
    | root-level fields. Use resolvers for dynamic values like request IDs.
    |
    */

    'extra_field_resolvers' => [
        // App\Support\ApiRequestIdField::class,
    ],

    'macros' => [
        'enabled' => true,
        'replace_existing' => false,
        'names' => [
            'success' => 'success',
            'error' => 'error',
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'validation_error' => 'validationError',
        ],
    ],

    'exceptions' => [
        'only_json_requests' => true,
        'expose_messages' => false,
    ],
];
