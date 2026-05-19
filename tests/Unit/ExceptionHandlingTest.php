<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Satheez\LaravelApiResponse\Support\ExceptionHandling;
use Satheez\LaravelApiResponse\Support\ExceptionResponseRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

it('renders validation exceptions', function () {
    $validator = Validator::make([], ['email' => ['required']]);
    $exception = new ValidationException($validator);

    $response = app(ExceptionResponseRenderer::class)->render($exception);

    expect($response->status())->toBe(422)
        ->and($response->getData(true)['errors'])->toHaveKey('email');
});

it('renders authentication exceptions', function () {
    $response = app(ExceptionResponseRenderer::class)->render(new AuthenticationException);

    expect($response->status())->toBe(401)
        ->and($response->getData(true)['success'])->toBeFalse();
});

it('renders authorization exceptions', function () {
    $response = app(ExceptionResponseRenderer::class)->render(new AuthorizationException);

    expect($response->status())->toBe(403);
});

it('renders not found exceptions', function () {
    expect(app(ExceptionResponseRenderer::class)->render(new ModelNotFoundException)->status())->toBe(404)
        ->and(app(ExceptionResponseRenderer::class)->render(new NotFoundHttpException)->status())->toBe(404);
});

it('does not expose model not found exception internals', function () {
    $exception = (new ModelNotFoundException)->setModel('App\Models\User', [1]);

    $response = app(ExceptionResponseRenderer::class)->render($exception);

    expect($response->status())->toBe(404)
        ->and($response->getData(true)['message'])->toBe('Resource not found.');
});

it('renders throttling exceptions', function () {
    $response = app(ExceptionResponseRenderer::class)->render(new TooManyRequestsHttpException);

    expect($response->status())->toBe(429);
});

it('hides fallback exception messages when debug is disabled', function () {
    config()->set('app.debug', false);

    $response = app(ExceptionResponseRenderer::class)->render(new RuntimeException('Database password leaked'));

    expect($response->status())->toBe(500)
        ->and($response->getData(true)['message'])->toBe('Something went wrong. Please try again later.');
});

it('can expose fallback exception messages when configured', function () {
    config()->set('app.debug', false);
    config()->set('api-response.exceptions.expose_messages', true);

    $response = app(ExceptionResponseRenderer::class)->render(new RuntimeException('Useful detail'));

    expect($response->getData(true)['message'])->toBe('Useful detail');
});

it('registers optional exception rendering only for json requests by default', function () {
    $exceptions = new TestExceptionConfiguration;
    ExceptionHandling::register($exceptions);

    $request = Request::create('/users');

    expect($exceptions->renderThrowable(new RuntimeException('Broken'), $request))->toBeNull();
});

it('renders registered exception responses for json requests', function () {
    $exceptions = new TestExceptionConfiguration;
    ExceptionHandling::register($exceptions);

    $request = Request::create('/users', 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $response = $exceptions->renderThrowable(new RuntimeException('Broken'), $request);

    expect($response->status())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->and($response->getData(true)['success'])->toBeFalse();
});

it('can render registered exceptions for non json requests when configured', function () {
    config()->set('api-response.exceptions.only_json_requests', false);

    $exceptions = new TestExceptionConfiguration;
    ExceptionHandling::register($exceptions);

    $request = Request::create('/users');
    $response = $exceptions->renderThrowable(new RuntimeException('Broken'), $request);

    expect($response->status())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});

class TestExceptionConfiguration extends Exceptions
{
    /**
     * @var callable|null
     */
    private mixed $renderer = null;

    public function __construct() {}

    public function render(callable $using)
    {
        $this->renderer = $using;

        return $this;
    }

    public function renderThrowable(Throwable $exception, Request $request): mixed
    {
        if (! is_callable($this->renderer)) {
            return null;
        }

        return ($this->renderer)($exception, $request);
    }
}
