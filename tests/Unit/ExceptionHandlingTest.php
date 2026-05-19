<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
