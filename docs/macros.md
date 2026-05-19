# Response Macros

The package registers macros on Laravel's response factory, so both styles work:

```php
use Illuminate\Support\Facades\Response;

Response::success($data);
response()->error('Invalid request');
```

## Collision Behavior

Existing app macros are preserved by default:

```php
'macros' => [
    'replace_existing' => false,
],
```

Set `replace_existing` to `true` when the package should overwrite existing macros with the same name.

## Custom Names

```php
'macros' => [
    'names' => [
        'success' => 'apiSuccess',
        'error' => 'apiError',
        'validation_error' => 'apiValidationError',
    ],
],
```

The config keys map to package methods. The values are the macro names registered on Laravel's response factory.
Macro names must be non-empty strings. Unknown macro keys fail because they do
not map to package response methods.
