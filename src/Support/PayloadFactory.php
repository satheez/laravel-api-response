<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Satheez\LaravelApiResponse\Data\ErrorBag;
use Satheez\LaravelApiResponse\Data\ResponseContext;
use Satheez\LaravelApiResponse\Data\ResponsePayload;

final readonly class PayloadFactory
{
    public function __construct(
        private DataNormalizer $dataNormalizer,
        private ErrorNormalizer $errorNormalizer,
        private ExtraFieldsResolver $extraFieldsResolver,
        private MessageResolver $messageResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public function make(
        bool $success,
        int $status,
        ?string $message,
        mixed $data,
        mixed $errors,
        array $meta,
        array $headers,
    ): ResponsePayload {
        $normalizedData = $this->dataNormalizer->normalize($data);
        $errorBag = new ErrorBag($this->errorNormalizer->normalize($errors));
        $resolvedMessage = $this->messageResolver->resolve($message);

        $context = new ResponseContext(
            success: $success,
            status: $status,
            message: $resolvedMessage,
            data: $normalizedData,
            errors: $errorBag,
            meta: $meta,
            headers: $headers,
            request: request(),
        );

        return new ResponsePayload(
            success: $success,
            message: $resolvedMessage,
            data: $normalizedData,
            errors: $errorBag->toArray(),
            meta: $meta,
            extra: $this->extraFieldsResolver->resolve($context),
        );
    }
}
