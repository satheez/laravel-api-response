# Upgrade Guide

## From `satheez/api-response` To `satheez/laravel-api-response`

This release is a breaking rewrite.

### Package Name

Replace the old Composer package:

```bash
composer remove satheez/api-response
composer require satheez/laravel-api-response
```

### Namespace

The namespace changed from:

```php
Satheez\ApiResponse
```

to:

```php
Satheez\LaravelApiResponse
```

Update facade imports:

```php
use Satheez\LaravelApiResponse\Facades\ApiResponse;
```

### Helpers

The preferred helper is now:

```php
api_response()->success();
```

The shorter helper remains available:

```php
api()->success();
```

### Response Shape

The old response shape used keys like `data`, `message`, and `error`. The new package always returns:

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": null,
    "errors": null,
    "meta": []
}
```

Update clients that previously read `error` to read `message` and `errors`.

### Config

Republish the config after upgrading:

```bash
php artisan vendor:publish --tag="api-response-config" --force
```

Custom top-level fields can be added through `extra_fields` or `extra_field_resolvers`.

### Localization

Default messages are now translation keys. Publish translations before customizing localized package messages:

```bash
php artisan vendor:publish --tag="api-response-translations"
```

Applications that prefer plain config strings can still replace the translation keys with literal messages.

### Extra Field Resolvers

Resolvers now receive a `ResponseContext`:

```php
use Satheez\LaravelApiResponse\Data\ResponseContext;

public function resolve(ResponseContext $context): array
{
    return ['response_status' => $context->status];
}
```

### Response Macros

Laravel response macros are enabled by default. Existing app macros are not replaced unless `macros.replace_existing` is set to `true`.
