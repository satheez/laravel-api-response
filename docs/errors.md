# Errors And Exception Rendering

The package includes explicit error helpers and optional exception rendering.
Both use the same response envelope.

## Error Helpers

All error helpers return `JsonResponse` with `"success": false`:

```php
// General error with custom message and status
api_response()->error('Payment failed.', 402);

// Validation error from various sources
api_response()->validationError($validator->errors());
api_response()->validationError($validationException);
api_response()->validationError(['email' => ['Invalid email.']]);

// Semantic error helpers
api_response()->unauthorized();          // 401
api_response()->forbidden();             // 403
api_response()->accessDenied();          // 403 (alias for forbidden)
api_response()->notFound();              // 404
api_response()->invalidRequest();        // 400
api_response()->somethingWentWrong();    // 500
```

Each helper uses a configurable default message from `config/api-response.php`.
Pass a custom message to override:

```php
api_response()->notFound('User not found.');
api_response()->unauthorized('Token expired.');
```

### Validation Error Response

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "data": null,
    "errors": {
        "email": ["The email field is required."],
        "name": ["The name must be at least 2 characters."]
    },
    "meta": []
}
```

### Exception Response

Convert any `Throwable` to an error response:

```php
try {
    $this->riskyOperation();
} catch (Throwable $e) {
    return api_response()->exception($e);
    // Or with custom message and status:
    return api_response()->exception($e, message: 'Service unavailable.', status: 503);
}
```

When no status is passed, the exception's `getCode()` is used if it falls within
the valid HTTP range (100–599). Otherwise, 500 is used.

---

## Exception Rendering

Exception rendering is opt-in. The package does not take over exception
rendering automatically.

### Registration

Register in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Exceptions;
use Satheez\LaravelApiResponse\Support\ExceptionHandling;

->withExceptions(function (Exceptions $exceptions): void {
    ExceptionHandling::register($exceptions);
})
```

### Exception Mappings

Once registered, the `ExceptionResponseRenderer` maps common Laravel exceptions
to the appropriate response method:

| Exception | Status | Response Method |
| --- | ---: | --- |
| `ValidationException` | 422 | `validationError()` |
| `AuthenticationException` | 401 | `unauthorized()` |
| `AuthorizationException` | 403 | `forbidden()` |
| `ModelNotFoundException` | 404 | `notFound()` |
| `NotFoundHttpException` | 404 | `notFound()` |
| `TooManyRequestsHttpException` | 429 | `error()` with rate limit message |
| `HttpExceptionInterface` | Exception status | `error()` with status-specific message |
| Other throwables | 500 | `error()` with fallback message |

### JSON-Only Rendering

By default, exception rendering only applies to requests that expect JSON
(`Accept: application/json` or XHR requests):

```php
'exceptions' => [
    'only_json_requests' => true,
],
```

Set to `false` for pure JSON APIs where every request should receive JSON error
responses regardless of the `Accept` header.

### Server Error Message Exposure

By default, generic server errors (500) do not expose exception messages. The
configured `errors.server_error` message is used instead:

```json
{
    "success": false,
    "message": "Something went wrong.",
    "data": null,
    "errors": null,
    "meta": []
}
```

Exception messages are exposed when either:
- `app.debug` is `true` (local development), or
- `api-response.exceptions.expose_messages` is `true`

```php
'exceptions' => [
    'expose_messages' => false, // true to always show exception messages
],
```

> **Tip:** Keep `expose_messages` set to `false` in production to avoid leaking
> internal error details to API consumers.

### Custom Exception Messages

For recognized exceptions (`AuthenticationException`, `AuthorizationException`,
`NotFoundHttpException`, `HttpExceptionInterface`), the exception's own message
is used when it is not empty. When the exception message is empty, the
configured default message for that status code is used.

For `ModelNotFoundException`, the message always uses the configured
`errors.not_found` message — the model class name is not exposed.

---

**Previous:** [Architecture](architecture.md) | **Next:** [Macros](macros.md)
