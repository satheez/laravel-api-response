# Laravel API Response

[![Tests](https://github.com/satheez/laravel-api-response/actions/workflows/tests.yml/badge.svg)](https://github.com/satheez/laravel-api-response/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/satheez/laravel-api-response.svg?style=flat-square)](https://packagist.org/packages/satheez/laravel-api-response)
[![Total Downloads](https://img.shields.io/packagist/dt/satheez/laravel-api-response.svg?style=flat-square)](https://packagist.org/packages/satheez/laravel-api-response)

Laravel API Response provides small, consistent helpers for returning JSON API responses from Laravel applications.

## Installation

Install the package with Composer:

```bash
composer require satheez/laravel-api-response
```

Publish the config file when you need to customize messages or add fields:

```bash
php artisan vendor:publish --tag="api-response-config"
```

Publish translations when you need localized package messages:

```bash
php artisan vendor:publish --tag="api-response-translations"
```

## Response Envelope

Every response uses the same base structure:

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": null,
    "errors": null,
    "meta": []
}
```

Error responses keep the same keys:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "data": null,
    "errors": {
        "email": ["The email field is required."]
    },
    "meta": []
}
```

## Usage

Use the helper, shorter compatibility helper, package facade, or Laravel response macros:

```php
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Satheez\LaravelApiResponse\Facades\ApiResponse;

final class UserController
{
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        return api_response()->created(
            data: new UserResource($user),
            message: 'User created successfully.',
        );
    }

    public function show(User $user): JsonResponse
    {
        return Response::success(new UserResource($user));
    }
}
```

Equivalent response styles:

```php
api_response()->success(['status' => 'ok']);
api()->error('Invalid request');
ApiResponse::created(['id' => 1]);
Response::success(['status' => 'ok']);
response()->error('Invalid request');
```

## Fluent Builder

```php
return api_response()
    ->builder()
    ->success()
    ->message('Loaded')
    ->data(['id' => 1])
    ->meta(['page' => 1])
    ->header('X-Trace-Id', $traceId)
    ->respond();
```

## Available Methods

| Method | Status | Purpose |
| --- | ---: | --- |
| `success($data = null, $message = null, $status = 200, $headers = [], $meta = [])` | Custom | General success response |
| `created($data = null, $message = null, $headers = [], $meta = [])` | 201 | Resource created response |
| `updated($data = null, $message = null, $headers = [], $meta = [])` | 200 | Resource updated response |
| `stored($data = null, $message = null, $headers = [], $meta = [])` | 200 | Alias for updated/stored responses |
| `deleted($data = null, $message = null, $headers = [], $meta = [])` | 200 | Resource deleted response |
| `error($message, $status = 422, $errors = null, $headers = [], $meta = [])` | Custom | General error response |
| `validationError($errors, $message = null, $headers = [], $meta = [])` | 422 | Validation error response |
| `unauthorized($message = null)` | 401 | Unauthenticated response |
| `forbidden($message = null)` | 403 | Unauthorized action response |
| `accessDenied($message = null)` | 403 | Alias for forbidden responses |
| `notFound($message = null)` | 404 | Missing resource response |
| `invalidRequest($message = null)` | 400 | Bad request response |
| `somethingWentWrong($message = null)` | 500 | Server error response |
| `exception($exception, $message = null, $status = null)` | Custom | Exception response |
| `resource($resource, $message = null, $status = 200, $headers = [], $meta = [])` | Custom | JSON resource response |
| `collection($collection, $resourceClass = null, $message = null, $status = 200, $headers = [], $meta = [])` | Custom | Resource collection response |
| `paginated($paginator, $resourceClass = null, $message = null, $status = 200, $headers = [], $meta = [])` | Custom | Paginated resource response |

## Resources And Pagination

```php
return api_response()->resource(new UserResource($user));

return api_response()->collection($users, UserResource::class);

return api_response()->paginated(
    paginator: User::query()->paginate(),
    resourceClass: UserResource::class,
);
```

Paginated responses keep items in `data` and move pagination details to `meta.pagination`.

## Extra Top-Level Fields

Add config-cache-safe static fields to every response in `config/api-response.php`:

```php
'extra_fields' => [
    'api_version' => 'v1',
    'service' => 'public-api',
],
```

These fields are appended at the root of the response:

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": null,
    "errors": null,
    "meta": [],
    "api_version": "v1",
    "service": "public-api"
}
```

Static values must be scalar, null, or arrays made from those values. Reserved fields cannot be overridden: `success`, `message`, `data`, `errors`, and `meta`.
Field keys must be strings. Invalid extra-field config fails with a package exception.

## Dynamic Extra Fields

Use resolver classes for dynamic values so your config remains cacheable. Resolvers receive a `ResponseContext`.

```php
namespace App\Support;

use Illuminate\Http\Request;
use Satheez\LaravelApiResponse\Contracts\ExtraFieldResolver;
use Satheez\LaravelApiResponse\Data\ResponseContext;

final readonly class RequestIdField implements ExtraFieldResolver
{
    public function __construct(private Request $request) {}

    public function resolve(ResponseContext $context): array
    {
        return [
            'request_id' => $this->request->headers->get('X-Request-Id'),
            'response_status' => $context->status,
        ];
    }
}
```

Register the resolver:

```php
'extra_field_resolvers' => [
    App\Support\RequestIdField::class,
],
```

Resolver classes must exist, implement `ExtraFieldResolver`, and return string field keys.

## Response Macros

Macros are enabled by default and are registered on Laravel's response factory. Existing app macros are not replaced unless configured.

```php
use Illuminate\Support\Facades\Response;

Response::success($data);
response()->validationError($errors);
```

See [docs/macros.md](docs/macros.md) for collision behavior and custom names.

## Localization

Default messages use Laravel translation keys in `config/api-response.php`:

```php
'messages' => [
    'success' => [
        'default' => 'api-response::messages.success.default',
    ],
],
```

After publishing translations, add or edit files under `lang/vendor/api-response/{locale}/messages.php`.
Laravel will resolve messages using the active application locale.

You can still use literal strings in config:

```php
'messages' => [
    'success' => [
        'default' => 'Everything is ready.',
    ],
],
```

## Optional Exception Handling

The package does not take over exception rendering automatically. Opt in from `bootstrap/app.php`:

```php
use Satheez\LaravelApiResponse\Support\ExceptionHandling;

->withExceptions(function (Exceptions $exceptions): void {
    ExceptionHandling::register($exceptions);
})
```

See [docs/errors.md](docs/errors.md) for mappings and server error behavior.

## Testing This Package

```bash
composer test
composer test-coverage
composer analyse
composer format-test
composer audit
```

## Upgrading

The rewrite changes the package name, namespace, and response envelope. See [docs/upgrade.md](docs/upgrade.md).

## License

The MIT License. See [LICENSE.md](LICENSE.md).
