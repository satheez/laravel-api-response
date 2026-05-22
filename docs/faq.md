# FAQ

## Does this package replace Form Requests?

No. Form Requests validate incoming HTTP input. This package formats outgoing
JSON responses. They are complementary — use Form Requests for input validation
and this package for consistent response formatting.

## Can I use this with Inertia or Livewire?

This package is designed for JSON API responses. Inertia and Livewire use their
own response mechanisms. Use this package for API endpoints that return JSON,
not for Inertia pages or Livewire component responses.

## How do I change the response envelope shape?

The envelope keys (`success`, `message`, `data`, `errors`, `meta`) are fixed by
design to provide a stable contract for API consumers. You can add extra
root-level fields using `extra_fields` and `extra_field_resolvers` in config,
but the core five keys cannot be renamed or removed.

If you need a fundamentally different envelope, extend `ResponseFactory` or
build responses manually using `response()->json()`.

## What happens if I pass a JsonResource to `success()`?

It works. `DataNormalizer` detects `JsonResource` instances and resolves them
to their array representation automatically. Using `resource()` is equivalent
to `success()` when passing a `JsonResource`.

## Can I use this outside controllers?

Yes. The `api_response()` helper, `api()` shortcut, and `ApiResponse` facade
work anywhere the Laravel service container is available: controllers, jobs,
middleware, console commands, event listeners, and service classes.

## How do extra field resolvers interact with config caching?

Static `extra_fields` in config are safe for `config:cache` because they contain
only scalar values, null, or arrays of those values. Closures and objects are
not allowed in `extra_fields`.

Dynamic values belong in `extra_field_resolvers`. Resolver classes are
instantiated at runtime through the container, so they work correctly with
`config:cache`. The resolver class name (a string) is what gets cached — not
the resolved values.

## What is ResponseContext used for?

`ResponseContext` is an immutable DTO passed to `ExtraFieldResolver::resolve()`.
It contains:

| Property | Type | Description |
| --- | --- | --- |
| `success` | `bool` | Whether this is a success response |
| `status` | `int` | HTTP status code |
| `message` | `?string` | Resolved response message |
| `data` | `mixed` | Normalized response data |
| `errors` | `?ErrorBag` | Normalized errors |
| `meta` | `array` | Meta array |
| `headers` | `array` | Response headers |
| `request` | `Request` | Current HTTP request |

This lets resolvers make decisions based on the full response state. For
example, you can include a `debug` field only on error responses:

```php
public function resolve(ResponseContext $context): array
{
    if ($context->success) {
        return [];
    }

    return ['debug_status' => $context->status];
}
```

## How do I handle API versioning?

The package does not manage API routing or versioning. Use Laravel's route
prefixing or middleware for version routing. The package can include version
metadata in every response using static extra fields:

```php
'extra_fields' => [
    'api_version' => 'v2',
],
```

Or use a resolver for dynamic version resolution based on the request:

```php
public function resolve(ResponseContext $context): array
{
    $version = $context->request->header('Accept-Version', 'v1');

    return ['api_version' => $version];
}
```

## What happens when exception rendering is not registered?

Nothing changes. The package does not interfere with Laravel's default exception
handling unless you explicitly call `ExceptionHandling::register()` in
`bootstrap/app.php`. Without it, Laravel handles exceptions as usual.

## Can I use response macros alongside the helper and facade?

Yes. All three access styles (`api_response()`, `ApiResponse::`, and
`Response::`) delegate to the same `ResponseFactory` and produce identical
output. Mix them freely based on your preferences.

## What is `only_json_requests` in the exceptions config?

When `exceptions.only_json_requests` is `true` (the default), the package's
exception renderer only handles requests that expect JSON responses
(requests with `Accept: application/json` or XHR requests). Non-JSON requests
fall through to Laravel's default exception handling.

Set it to `false` if your application is a pure JSON API and every request
should receive JSON error responses.

## Do extra field keys need to be unique across static fields and resolvers?

Static fields are applied first, then resolver fields are merged on top. If a
resolver returns a key that matches a static field, the resolver's value wins.
If multiple resolvers return the same key, the last resolver in the
`extra_field_resolvers` array wins.

Reserved keys (`success`, `message`, `data`, `errors`, `meta`) can never be
used as extra field keys. The package throws `InvalidConfigurationException`
immediately if a reserved key is detected.

## Why does the package throw exceptions for invalid config?

Invalid configuration (non-string keys, reserved field overrides, invalid macro
names) throws exceptions during service provider boot rather than silently
producing broken responses. This ensures deployment issues are visible
immediately instead of causing subtle bugs in production.

---

**Previous:** [Examples and recipes](examples.md)
