# Installation

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `^8.3` |
| Laravel | `^12.0` or `^13.0` |

## Install With Composer

```bash
composer require satheez/laravel-api-response
```

Laravel package discovery registers the service provider and facade
automatically. No manual registration is needed.

## Publish Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag="api-response-config"
```

This creates:

```text
config/api-response.php
```

> **Note:** The package works without publishing the config — sensible defaults
> are applied. Publish only when you need to customize messages, add extra
> fields, configure macros, or change exception behavior.

## Publish Translations

Publish translations when you need to customize or localize the default response
messages:

```bash
php artisan vendor:publish --tag="api-response-translations"
```

This publishes language files under `lang/vendor/api-response/`. Laravel will
resolve messages using the active application locale.

## Verify Installation

After installing, verify the package is working by returning a response from any
route or controller:

```php
Route::get('/health', fn () => api_response()->success(['status' => 'ok']));
```

The response will be:

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": {
        "status": "ok"
    },
    "errors": null,
    "meta": []
}
```

## Access Styles

The package provides four equivalent ways to build responses. Choose whichever
fits your project's conventions:

```php
// 1. Helper function (recommended)
api_response()->success($data);

// 2. Shorter alias
api()->success($data);

// 3. Facade
use Satheez\LaravelApiResponse\Facades\ApiResponse;
ApiResponse::success($data);

// 4. Response macros
use Illuminate\Support\Facades\Response;
Response::success($data);
response()->success($data);
```

All four styles delegate to the same `ResponseFactory` and produce identical
output.

---

**Next:** [Usage guide](usage.md)
