<?php

declare(strict_types=1);

namespace Satheez\LaravelApiResponse\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use JsonSerializable;
use Stringable;
use Traversable;

final readonly class DataNormalizer
{
    public function normalize(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve(request());
        }

        if ($data instanceof Responsable) {
            $response = $data->toResponse(request());

            if ($response instanceof JsonResponse) {
                return $response->getData(true);
            }

            return $response->getContent();
        }

        if ($data instanceof AbstractPaginator) {
            return $this->normalize($data->items());
        }

        if ($data instanceof Arrayable) {
            return $this->normalize($data->toArray());
        }

        if ($data instanceof JsonSerializable) {
            return $this->normalize($data->jsonSerialize());
        }

        if ($data instanceof Stringable) {
            return (string) $data;
        }

        if ($data instanceof Traversable) {
            return $this->normalize(iterator_to_array($data));
        }

        if (is_array($data)) {
            return array_map(fn (mixed $value): mixed => $this->normalize($value), $data);
        }

        return $data;
    }

    /**
     * @param  iterable<mixed>  $items
     * @return array<int|string, mixed>
     */
    public function collection(iterable $items): array
    {
        if ($items instanceof Arrayable) {
            $items = $items->toArray();
        }

        if ($items instanceof Traversable) {
            $items = iterator_to_array($items);
        }

        $normalized = [];

        foreach ($items as $key => $value) {
            $normalized[$key] = $this->normalize($value);
        }

        return $normalized;
    }
}
