# Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --tag="api-response-config"
```

The file is published to `config/api-response.php`.

## Options

| Option | Default | Purpose |
| --- | --- | --- |
| `messages.success.*` | Translation keys | Default messages for success responses. |
| `messages.errors.*` | Translation keys | Default messages for error responses. |
| `extra_fields` | `[]` | Static root-level fields added to every response. |
| `extra_field_resolvers` | `[]` | Resolver classes for dynamic root-level fields. |
| `macros.enabled` | `true` | Register package methods as Laravel response macros. |
| `macros.replace_existing` | `false` | Whether package macros overwrite existing app macros. |
| `macros.names` | See below | Map of package methods to macro names. |
| `exceptions.only_json_requests` | `true` | Only render JSON errors for requests expecting JSON. |
| `exceptions.expose_messages` | `false` | Show exception messages in non-debug environments. |

## Messages

Messages may be translation keys or literal strings:

```php
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
```

When a value looks like a translation key, `MessageResolver` passes it through
Laravel's `__()` helper. You can use literal strings instead:

```php
'messages' => [
    'success' => [
        'default' => 'Everything is ready.',
    ],
],
```

Configured message values must be strings. Invalid message config fails with
`InvalidConfigurationException` instead of returning an empty response message.

### Localization

After publishing translations, add or edit files under
`lang/vendor/api-response/{locale}/messages.php`. Laravel will resolve messages
using the active application locale.

```bash
php artisan vendor:publish --tag="api-response-translations"
```

## Extra Fields

### Static Extra Fields

Static extra fields are appended to every response at the root level. Values
must be scalar, null, or arrays made from those values (config-cache-safe):

```php
'extra_fields' => [
    'api_version' => 'v1',
    'service' => 'public-api',
],
```

Response output:

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

Reserved envelope keys cannot be overridden: `success`, `message`, `data`,
`errors`, and `meta`. Field keys must be strings. Invalid extra-field config
fails with `InvalidConfigurationException`.

### Dynamic Extra Fields (Resolvers)

Use resolver classes for dynamic values so your config remains cacheable.
Resolvers receive a `ResponseContext`:

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

Resolver classes must exist, implement `ExtraFieldResolver`, and return string
field keys. If a resolver returns a key that collides with a static field, the
resolver's value wins. Reserved keys always fail with an exception.

## Macros

Macros are enabled by default:

```php
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
```

Macro names must be non-empty strings. Malformed macro config fails during
package boot so deployment issues are visible immediately.

See [macros.md](macros.md) for collision behavior and custom names.

## Exceptions

Exception rendering is opt-in. Register in `bootstrap/app.php`:

```php
use Satheez\LaravelApiResponse\Support\ExceptionHandling;

->withExceptions(function (Exceptions $exceptions): void {
    ExceptionHandling::register($exceptions);
})
```

| Option | Default | Purpose |
| --- | --- | --- |
| `only_json_requests` | `true` | Only render JSON errors for requests expecting JSON. |
| `expose_messages` | `false` | Show exception messages for server errors in production. |

When `expose_messages` is `false` (the default), generic server errors use the
configured `errors.server_error` message. When `true` or when `app.debug` is
`true`, the actual exception message is included.

See [errors.md](errors.md) for exception mappings and behavior details.

---

**Previous:** [Usage guide](usage.md) | **Next:** [Architecture](architecture.md)
