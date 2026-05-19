# Errors And Exception Rendering

The package includes explicit error helpers and optional exception rendering.

## Error Helpers

```php
api_response()->validationError($validator->errors());
api_response()->unauthorized();
api_response()->forbidden();
api_response()->notFound();
api_response()->somethingWentWrong();
```

## Exception Rendering

Register optional rendering in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Exceptions;
use Satheez\LaravelApiResponse\Support\ExceptionHandling;

->withExceptions(function (Exceptions $exceptions): void {
    ExceptionHandling::register($exceptions);
})
```

## Mappings

| Exception | Status |
| --- | ---: |
| `ValidationException` | 422 |
| `AuthenticationException` | 401 |
| `AuthorizationException` | 403 |
| `ModelNotFoundException` | 404 |
| `NotFoundHttpException` | 404 |
| `TooManyRequestsHttpException` | 429 |
| `HttpExceptionInterface` | Exception status |
| Other throwables | 500 |

By default, generic server errors do not expose exception messages unless `app.debug` is true or `api-response.exceptions.expose_messages` is true.
