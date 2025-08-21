<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

/**
 * Processor for built-in PHP types (null, scalar, array).
 *
 * Handles null and scalar values by passing them through as-is since they are already
 * suitable for structured logging. Arrays are processed recursively to handle nested structures.
 *
 * This consolidates the functionality of the former NullProcessor, ScalarProcessor, and ArrayProcessor
 * for better performance and simpler architecture.
 *
 * @internal This class is internal to the PSR Logger implementation and should not be used directly.
 *
 * @implements ContextProcessorInterface<null|scalar|array<array-key, mixed>, null|scalar|array<array-key, mixed>>
 */
class BuiltInTypeProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return $value === null || \is_scalar($value) || \is_array($value);
    }

    /**
     * @param null|scalar|array<array-key, mixed> $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return null|scalar|array<array-key, mixed>
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        // Handle arrays recursively
        if (\is_array($value)) {
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

        // Null and scalar values are already suitable for logging
        return $value;
    }
}
