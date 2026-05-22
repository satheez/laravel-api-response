# Examples & Recipes

This page covers common use cases and patterns for using Laravel API Response in
your projects.

---

## 1. CRUD Controller

A typical resource controller using all the standard response methods:

```php
namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function index(): JsonResponse
    {
        return api_response()->paginated(
            paginator: Post::query()->latest()->paginate(),
            resourceClass: PostResource::class,
            message: 'Posts retrieved successfully.',
        );
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = Post::query()->create($request->validated());

        return api_response()->created(
            data: new PostResource($post),
            message: 'Post created successfully.',
        );
    }

    public function show(Post $post): JsonResponse
    {
        return api_response()->success(
            data: new PostResource($post),
        );
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post->update($request->validated());

        return api_response()->updated(
            data: new PostResource($post),
            message: 'Post updated successfully.',
        );
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return api_response()->deleted(
            message: 'Post deleted successfully.',
        );
    }
}
```

---

## 2. Form Request + Validation Error

When using Form Requests, Laravel throws a `ValidationException` automatically.
If you have registered exception handling, validation errors are returned in the
standard envelope format.

For manual validation:

```php
use Illuminate\Support\Facades\Validator;

public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'email' => ['required', 'email'],
        'name' => ['required', 'string', 'max:255'],
    ]);

    if ($validator->fails()) {
        return api_response()->validationError(
            errors: $validator->errors(),
            message: 'Please fix the following errors.',
        );
    }

    // Process valid data...
    return api_response()->created();
}
```

Response:

```json
{
    "success": false,
    "message": "Please fix the following errors.",
    "data": null,
    "errors": {
        "email": ["The email field is required."],
        "name": ["The name field is required."]
    },
    "meta": []
}
```

---

## 3. Paginated API Endpoint

Return paginated results with automatic pagination meta:

```php
public function index(Request $request): JsonResponse
{
    $users = User::query()
        ->when($request->input('search'), fn ($q, $search) =>
            $q->where('name', 'like', "%{$search}%")
        )
        ->paginate($request->input('per_page', 15));

    return api_response()->paginated(
        paginator: $users,
        resourceClass: UserResource::class,
    );
}
```

When using simple pagination (cursor-based) without a total count:

```php
$users = User::query()->simplePaginate(15);

return api_response()->paginated(
    paginator: $users,
    resourceClass: UserResource::class,
);
```

The `total` and `last_page` fields are omitted automatically when the paginator
does not support them.

---

## 4. Dynamic Extra Fields

Add a request ID and response timing to every API response.

Create a resolver class:

```php
namespace App\Support;

use Illuminate\Http\Request;
use Satheez\LaravelApiResponse\Contracts\ExtraFieldResolver;
use Satheez\LaravelApiResponse\Data\ResponseContext;

final readonly class ApiMetadataResolver implements ExtraFieldResolver
{
    public function __construct(
        private Request $request,
    ) {}

    public function resolve(ResponseContext $context): array
    {
        return [
            'request_id' => $this->request->headers->get('X-Request-Id'),
            'response_time_ms' => round(
                (microtime(true) - LARAVEL_START) * 1000,
                2,
            ),
        ];
    }
}
```

Register in `config/api-response.php`:

```php
'extra_field_resolvers' => [
    App\Support\ApiMetadataResolver::class,
],
```

Every response now includes:

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": null,
    "errors": null,
    "meta": [],
    "request_id": "req-abc123",
    "response_time_ms": 42.5
}
```

---

## 5. Exception Handling

Register the package's exception rendering in `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Exceptions;
use Satheez\LaravelApiResponse\Support\ExceptionHandling;

->withExceptions(function (Exceptions $exceptions): void {
    ExceptionHandling::register($exceptions);
})
```

Now all common exceptions automatically return the standard envelope:

```php
// In a controller — this will return a 404 JSON response automatically
$user = User::query()->findOrFail($id);
```

```json
{
    "success": false,
    "message": "Resource not found.",
    "data": null,
    "errors": null,
    "meta": []
}
```

---

## 6. Fluent Builder for Complex Responses

When a response needs conditional logic, the builder keeps things clean:

```php
public function process(Request $request): JsonResponse
{
    $result = $this->processor->run($request->input('task_id'));

    $builder = api_response()->builder();

    if ($result->successful()) {
        $builder
            ->success()
            ->message('Task completed.')
            ->data($result->output())
            ->meta(['duration_ms' => $result->durationMs()]);
    } else {
        $builder
            ->error('Task failed.', 422)
            ->errors($result->errors())
            ->meta(['retryable' => $result->isRetryable()]);
    }

    return $builder
        ->header('X-Task-Id', $result->taskId())
        ->respond();
}
```

---

## 7. API Versioning with Extra Fields

Add a static API version to every response:

```php
// config/api-response.php

'extra_fields' => [
    'api_version' => 'v2',
    'service' => 'user-service',
],
```

Every response from this service now includes:

```json
{
    "success": true,
    "message": "Request completed successfully.",
    "data": null,
    "errors": null,
    "meta": [],
    "api_version": "v2",
    "service": "user-service"
}
```

---

## 8. Using the Facade

The facade is useful in service classes, event listeners, or anywhere the helper
function feels less appropriate:

```php
use Satheez\LaravelApiResponse\Facades\ApiResponse;

final class PaymentWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $payment = $this->processPayment($request);

        if ($payment->failed()) {
            return ApiResponse::error(
                message: 'Payment processing failed.',
                status: 402,
                errors: ['reason' => [$payment->failureReason()]],
            );
        }

        return ApiResponse::success(
            data: ['payment_id' => $payment->id],
            message: 'Payment processed successfully.',
        );
    }
}
```

---

## 9. Collection Without Resource Class

Return a plain collection when you don't need a resource transformation:

```php
$settings = collect([
    ['key' => 'theme', 'value' => 'dark'],
    ['key' => 'locale', 'value' => 'en'],
]);

return api_response()->collection($settings);
```

---

## 10. Custom Headers

Add custom headers to any response:

```php
return api_response()->success(
    data: $data,
    headers: [
        'X-RateLimit-Remaining' => '99',
        'X-RateLimit-Reset' => '1716400000',
    ],
);
```

---

**Previous:** [Macros](macros.md) | **Next:** [FAQ](faq.md)
