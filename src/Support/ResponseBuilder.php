<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Satheez\LaravelApiResponse\ResponseFactory;

final class ResponseBuilder
{
    private bool $success = true;

    private int $status = Response::HTTP_OK;

    private ?string $message = null;

    private mixed $data = null;

    private mixed $errors = null;

    /**
     * @var array<string, mixed>
     */
    private array $meta = [];

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    public function __construct(
        private readonly ResponseFactory $factory,
    ) {}

    public function success(int $status = Response::HTTP_OK): self
    {
        $this->success = true;
        $this->status = $status;

        return $this;
    }

    public function error(?string $message = null, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): self
    {
        $this->success = false;
        $this->status = $status;
        $this->message = $message;

        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function message(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function errors(mixed $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function respond(): JsonResponse
    {
        if ($this->success) {
            return $this->factory->success($this->data, $this->message, $this->status, $this->headers, $this->meta);
        }

        return $this->factory->error(
            message: $this->message ?? '',
            status: $this->status,
            errors: $this->errors,
            headers: $this->headers,
            meta: $this->meta,
        );
    }
}
