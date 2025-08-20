<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Processor for arrays and nested arrays.
 *
 * Recursively processes array elements to handle complex nested structures.
 *
 * @implements ContextProcessorInterface<array<array-key, mixed>, array<array-key, mixed>>
 */
class ArrayProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return \is_array($value);
    }

    /**
     * @param array<array-key, mixed> $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return array<array-key, mixed>
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        /** @var array<array-key, mixed> $processed */
        $processed = [];

        /**
         * @var array-key $key
         * @var mixed $item
         */
        foreach ($value as $key => $item) {
            /** @psalm-suppress MixedAssignment - Intentionally processing mixed types */
            $processed[$key] = $recursiveProcessor($item);
        }

        return $processed;
    }
}
