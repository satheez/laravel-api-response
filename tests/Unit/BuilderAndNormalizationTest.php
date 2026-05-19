<?php

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Stringable;
use Illuminate\Validation\ValidationException;

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

it('normalizes json resources', function () {
    $response = api_response()->resource(new TestUserResource(['name' => 'Taylor']));

    expect($response->getData(true)['data'])->toBe(['name' => 'Taylor']);
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

it('normalizes arrayable and json serializable data', function () {
    $response = api_response()->success(new Stringable('ready'));

    expect($response->getData(true)['data'])->toBe('ready');
});

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
