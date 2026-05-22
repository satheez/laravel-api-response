# Response Macros

The package registers macros on Laravel's response factory at boot time, so both
the facade and the helper styles work:

```php
use Illuminate\Support\Facades\Response;

Response::success($data);
response()->error('Invalid request');
```

## How Macros Are Registered

When the service provider boots, `ResponseMacroRegistrar` reads the
`macros.names` config and registers each entry as a macro on Laravel's
`ResponseFactory`. Each macro delegates to the corresponding method on the
package's `ResponseFactory`.

## Available Macros

The default macro configuration maps these package methods:

| Config Key | Macro Name | Package Method |
| --- | --- | --- |
| `success` | `success` | `ResponseFactory::success()` |
| `error` | `error` | `ResponseFactory::error()` |
| `created` | `created` | `ResponseFactory::created()` |
| `updated` | `updated` | `ResponseFactory::updated()` |
| `deleted` | `deleted` | `ResponseFactory::deleted()` |
| `validation_error` | `validationError` | `ResponseFactory::validationError()` |

Usage:

```php
Response::success($data);
Response::created($data, 'User created.');
Response::error('Not allowed.', 403);
Response::validationError($validator->errors());
response()->success($data);
response()->deleted();
```

## Collision Behavior

Existing app macros are preserved by default:

```php
'macros' => [
    'replace_existing' => false,
],
```

If your application already registers a macro named `success` on the response
factory, the package will skip registering its own `success` macro. This
prevents breaking existing application behavior.

Set `replace_existing` to `true` when the package should overwrite existing
macros with the same name:

```php
'macros' => [
    'replace_existing' => true,
],
```

## Custom Names

Rename macros to avoid conflicts or match your naming conventions:

```php
'macros' => [
    'names' => [
        'success' => 'apiSuccess',
        'error' => 'apiError',
        'created' => 'apiCreated',
        'updated' => 'apiUpdated',
        'deleted' => 'apiDeleted',
        'validation_error' => 'apiValidationError',
    ],
],
```

Usage with custom names:

```php
Response::apiSuccess($data);
response()->apiError('Something went wrong.', 500);
```

The config keys map to package methods. The values are the macro names
registered on Laravel's response factory. Macro names must be non-empty
strings. Unknown macro keys fail because they do not map to package response
methods.

## Disabling Macros

To disable macro registration entirely:

```php
'macros' => [
    'enabled' => false,
],
```

When macros are disabled, you can still use the `api_response()` helper, the
`api()` shortcut, or the `ApiResponse` facade. Only the `Response::` and
`response()->` macro-style calls become unavailable.

## Error Handling

The package validates macro configuration at boot time and throws exceptions
for invalid config:

| Error | Exception |
| --- | --- |
| Non-string macro key | `InvalidConfigurationException` |
| Non-string macro name | `InvalidConfigurationException` |
| Empty macro name | `InvalidConfigurationException` |
| Unknown method mapping | `MacroRegistrationException` |

These errors surface immediately during deployment rather than causing silent
failures at runtime.

---

**Previous:** [Errors and exception rendering](errors.md) | **Next:** [Examples and recipes](examples.md)
