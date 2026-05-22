# Usage Guide

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

This consistent envelope makes it easy for API consumers to parse every response
with the same logic regardless of success or failure.

---

## Success Responses

### General Success

```php
return api_response()->success(
    data: ['status' => 'ok'],
    message: 'Everything is ready.',
);
```

The `success()` method defaults to HTTP 200. Pass a custom status code as the
third argument:

```php
return api_response()->success($data, status: 202);
```

### Created

Returns HTTP 201:

```php
return api_response()->created(
    data: new UserResource($user),
    message: 'User created successfully.',
);
```

### Updated

Returns HTTP 200 with an update-specific default message:

```php
return api_response()->updated(data: new UserResource($user));
```

### Stored

Alias for `updated()`. Use whichever reads better in your controller:

```php
return api_response()->stored(data: $record);
```

### Deleted

Returns HTTP 200 with a deletion-specific default message:

```php
return api_response()->deleted();
```

---

## Error Responses

### General Error

```php
return api_response()->error(
    message: 'Payment failed.',
    status: 402,
    errors: ['charge_id' => ['Charge was declined.']],
);
```

The default status is 422 when omitted.

### Validation Error

Accepts arrays, strings, `MessageBag`, or `ValidationException`:

```php
// From a Validator instance
return api_response()->validationError($validator->errors());

// From a ValidationException
return api_response()->validationError($exception);

// From a plain array
return api_response()->validationError([
    'email' => ['The email field is required.'],
    'name' => ['The name must be at least 2 characters.'],
]);
```

### Unauthorized (401)

```php
return api_response()->unauthorized();
return api_response()->unauthorized('Token expired.');
```

### Forbidden (403)

```php
return api_response()->forbidden();
return api_response()->forbidden('You do not have permission.');
```

### Access Denied

Alias for `forbidden()`:

```php
return api_response()->accessDenied();
```

### Not Found (404)

```php
return api_response()->notFound();
return api_response()->notFound('User not found.');
```

### Invalid Request (400)

```php
return api_response()->invalidRequest();
return api_response()->invalidRequest('Malformed JSON body.');
```

### Server Error (500)

```php
return api_response()->somethingWentWrong();
```

### Exception Response

Converts any `Throwable` to a JSON error response:

```php
return api_response()->exception($exception);
return api_response()->exception($exception, message: 'Custom message', status: 503);
```

When no status is passed, the exception's `getCode()` is used if it falls within
the valid HTTP range (100–599). Otherwise, 500 is used.

---

## Resources And Pagination

### Single Resource

Wrap a `JsonResource` in the standard envelope:

```php
return api_response()->resource(new UserResource($user));
```

### Collection

Return a collection, optionally transforming items through a resource class:

```php
// Plain collection
return api_response()->collection($users);

// With a resource class
return api_response()->collection($users, UserResource::class);

// Already a ResourceCollection
return api_response()->collection(UserResource::collection($users));
```

### Paginated Response

Paginated responses keep items in `data` and move pagination details to
`meta.pagination`:

```php
return api_response()->paginated(
    paginator: User::query()->paginate(),
    resourceClass: UserResource::class,
);
```

The pagination meta includes:

```json
{
    "meta": {
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "from": 1,
            "to": 15,
            "total": 50,
            "last_page": 4,
            "path": "https://example.com/api/users",
            "first_page_url": "https://example.com/api/users?page=1",
            "last_page_url": "https://example.com/api/users?page=4",
            "next_page_url": "https://example.com/api/users?page=2",
            "prev_page_url": null
        }
    }
}
```

---

## Fluent Builder

For complex responses where you want to set each part explicitly, use the fluent
builder:

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

The builder supports the following methods:

| Method | Purpose |
| --- | --- |
| `success($status = 200)` | Mark as a success response |
| `error($message = null, $status = 422)` | Mark as an error response |
| `status($status)` | Override the HTTP status code |
| `message($message)` | Set the response message |
| `data($data)` | Set the response data |
| `errors($errors)` | Set the error details |
| `meta($meta)` | Set the meta array |
| `header($name, $value)` | Add a single response header |
| `headers($headers)` | Merge multiple response headers |
| `respond()` | Build and return the `JsonResponse` |

Error builder example:

```php
return api_response()
    ->builder()
    ->error('Rate limit exceeded', 429)
    ->meta(['retry_after' => 60])
    ->header('Retry-After', '60')
    ->respond();
```

---

## Method Reference

| Method | Status | Purpose |
| --- | ---: | --- |
| `success($data, $message, $status, $headers, $meta)` | Custom | General success response |
| `created($data, $message, $headers, $meta)` | 201 | Resource created response |
| `updated($data, $message, $headers, $meta)` | 200 | Resource updated response |
| `stored($data, $message, $headers, $meta)` | 200 | Alias for updated/stored responses |
| `deleted($data, $message, $headers, $meta)` | 200 | Resource deleted response |
| `error($message, $status, $errors, $headers, $meta)` | Custom | General error response |
| `validationError($errors, $message, $headers, $meta)` | 422 | Validation error response |
| `unauthorized($message)` | 401 | Unauthenticated response |
| `forbidden($message)` | 403 | Unauthorized action response |
| `accessDenied($message)` | 403 | Alias for forbidden responses |
| `notFound($message)` | 404 | Missing resource response |
| `invalidRequest($message)` | 400 | Bad request response |
| `somethingWentWrong($message)` | 500 | Server error response |
| `exception($exception, $message, $status)` | Custom | Exception response |
| `resource($resource, $message, $status, $headers, $meta)` | Custom | JSON resource response |
| `collection($collection, $resourceClass, $message, $status, $headers, $meta)` | Custom | Resource collection response |
| `paginated($paginator, $resourceClass, $message, $status, $headers, $meta)` | Custom | Paginated resource response |

All parameters except the first are optional and use sensible defaults.

---

**Previous:** [Installation](installation.md) | **Next:** [Configuration](configuration.md)
