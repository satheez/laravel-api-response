<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Stringable;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

it('builds responses through a fluent builder', function () {
    $response = api_response()
        ->builder()
        ->success()
        ->message('Loaded')
        ->data(['id' => 1])
        ->meta(['page' => 1])
        ->header('X-Trace-Id', 'abc')
        ->respond();

    expect($response->status())->toBe(200)
        ->and($response->headers->get('X-Trace-Id'))->toBe('abc')
        ->and($response->getData(true))->toMatchArray([
            'success' => true,
            'message' => 'Loaded',
            'data' => ['id' => 1],
            'meta' => ['page' => 1],
        ]);
});

it('builds error responses through a fluent builder', function () {
    $response = api_response()
        ->builder()
        ->error('Conflict')
        ->status(409)
        ->errors(['state' => ['Already locked']])
        ->headers(['X-Trace-Id' => 'abc'])
        ->respond();

    expect($response->status())->toBe(409)
        ->and($response->headers->get('X-Trace-Id'))->toBe('abc')
        ->and($response->getData(true))->toMatchArray([
            'success' => false,
            'message' => 'Conflict',
            'errors' => ['state' => ['Already locked']],
        ]);
});

it('builds error responses with an empty message when none is provided', function () {
    $response = api_response()
        ->builder()
        ->error()
        ->respond();

    expect($response->status())->toBe(422)
        ->and($response->getData(true)['message'])->toBe('');
});

it('normalizes json resources', function () {
    $response = api_response()->resource(new TestUserResource(['name' => 'Taylor']));

    expect($response->getData(true)['data'])->toBe(['name' => 'Taylor']);
});

it('normalizes responsable json responses', function () {
    $response = api_response()->success(new TestJsonResponsable);

    expect($response->getData(true)['data'])->toBe(['ready' => true]);
});

it('normalizes responsable non json responses', function () {
    $response = api_response()->success(new TestTextResponsable);

    expect($response->getData(true)['data'])->toBe('ready');
});

it('normalizes paginators passed as data', function () {
    $paginator = new Paginator(
        items: [['name' => 'Taylor']],
        perPage: 1,
        currentPage: 1,
        options: ['path' => '/users'],
    );

    $response = api_response()->success($paginator);

    expect($response->getData(true)['data'])->toBe([['name' => 'Taylor']]);
});

it('normalizes traversable data recursively', function () {
    $response = api_response()->success(new ArrayIterator([
        'user' => new TestArrayableData,
    ]));

    expect($response->getData(true)['data'])->toBe([
        'user' => ['name' => 'Taylor'],
    ]);
});

it('normalizes resource collections', function () {
    $response = api_response()->collection([
        ['name' => 'Taylor'],
        ['name' => 'Abigail'],
    ], TestUserResource::class);

    expect($response->getData(true)['data'])->toBe([
        ['name' => 'Taylor'],
        ['name' => 'Abigail'],
    ]);
});

it('normalizes laravel resource collections', function () {
    $resourceCollection = TestUserResource::collection([
        ['name' => 'Taylor'],
    ]);

    $response = api_response()->collection($resourceCollection);

    expect($response->getData(true)['data'])->toBe([
        ['name' => 'Taylor'],
    ]);
});

it('normalizes plain collections without a resource class', function () {
    $response = api_response()->collection(new Collection([
        new TestArrayableData,
    ]));

    expect($response->getData(true)['data'])->toBe([
        ['name' => 'Taylor'],
    ]);
});

it('normalizes paginated data without a resource class', function () {
    $paginator = new LengthAwarePaginator(
        items: [new TestArrayableData],
        total: 1,
        perPage: 1,
        currentPage: 1,
        options: ['path' => '/users'],
    );

    $response = api_response()->paginated($paginator);
    $payload = $response->getData(true);

    expect($payload['data'])->toBe([
        ['name' => 'Taylor'],
    ])->and($payload['meta']['pagination'])->toMatchArray([
        'current_page' => 1,
        'total' => 1,
    ]);
});

it('normalizes paginated data into data and meta', function () {
    $paginator = new LengthAwarePaginator(
        items: [['name' => 'Taylor']],
        total: 3,
        perPage: 1,
        currentPage: 2,
        options: ['path' => '/users'],
    );

    $response = api_response()->paginated($paginator, TestUserResource::class);
    $payload = $response->getData(true);

    expect($payload['data'])->toBe([['name' => 'Taylor']])
        ->and($payload['meta']['pagination'])->toMatchArray([
            'current_page' => 2,
            'per_page' => 1,
            'total' => 3,
            'last_page' => 3,
        ]);
});

it('normalizes message bags for validation errors', function () {
    $response = api_response()->validationError(new MessageBag([
        'email' => ['The email field is required.'],
    ]));

    expect($response->getData(true)['errors'])->toBe([
        'email' => ['The email field is required.'],
    ]);
});

it('normalizes validation exceptions', function () {
    $validator = Validator::make([], ['email' => ['required']]);
    $exception = new ValidationException($validator);

    $response = api_response()->validationError($exception);

    expect($response->getData(true)['errors'])->toHaveKey('email');
});

it('normalizes arrayable data', function () {
    $response = api_response()->success(new TestArrayableData);

    expect($response->getData(true)['data'])->toBe(['name' => 'Taylor']);
});

it('normalizes json serializable data', function () {
    $response = api_response()->success(new TestJsonSerializableData);

    expect($response->getData(true)['data'])->toBe(['name' => 'Taylor']);
});

it('normalizes stringable data', function () {
    $response = api_response()->success(new Stringable('ready'));

    expect($response->getData(true)['data'])->toBe('ready');
});

it('normalizes throwable errors', function () {
    $response = api_response()->error('Failed', errors: new RuntimeException('Broken'));

    expect($response->getData(true)['errors'])->toBe([
        'exception' => ['Broken'],
    ]);
});

it('normalizes string and scalar errors', function () {
    expect(api_response()->error('Failed', errors: 'Broken')->getData(true)['errors'])->toBe([
        'message' => ['Broken'],
    ])->and(api_response()->error('Failed', errors: 10)->getData(true)['errors'])->toBe([
        'message' => ['10'],
    ]);
});

it('normalizes arrayable and stringable errors', function () {
    expect(api_response()->error('Failed', errors: new TestArrayableErrors)->getData(true)['errors'])->toBe([
        'email' => ['Required'],
    ])->and(api_response()->error('Failed', errors: new Stringable('Broken'))->getData(true)['errors'])->toBe([
        'message' => ['Broken'],
    ]);
});

it('normalizes unsupported error objects into a safe message', function () {
    $response = api_response()->error('Failed', errors: new stdClass);

    expect($response->getData(true)['errors'])->toBe([
        'message' => ['Invalid error payload.'],
    ]);
});

class TestJsonResponsable implements Responsable
{
    public function toResponse($request): SymfonyResponse
    {
        return response()->json(['ready' => true]);
    }
}

class TestTextResponsable implements Responsable
{
    public function toResponse($request): SymfonyResponse
    {
        return response('ready');
    }
}

class TestArrayableData implements Arrayable
{
    /**
     * @return array{name: string}
     */
    public function toArray(): array
    {
        return ['name' => 'Taylor'];
    }
}

class TestJsonSerializableData implements JsonSerializable
{
    /**
     * @return array{name: string}
     */
    public function jsonSerialize(): array
    {
        return ['name' => 'Taylor'];
    }
}

class TestArrayableErrors implements Arrayable
{
    /**
     * @return array{email: array<int, string>}
     */
    public function toArray(): array
    {
        return ['email' => ['Required']];
    }
}

class TestUserResource extends JsonResource
{
    /**
     * @return array{name: string}
     */
    public function toArray($request): array
    {
        return ['name' => $this->resource['name']];
    }
}
