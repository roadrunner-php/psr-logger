<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Processor for scalar values (string, int, float, bool).
 *
 * These values are passed through as-is since they are already
 * suitable for structured logging. Note: null values are handled
 * by the dedicated NullProcessor.
 *
 * @implements ContextProcessorInterface<scalar, scalar>
 */
class ScalarProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return \is_scalar($value);
    }

    /**
     * @param scalar $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return scalar
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        // Scalar values are already suitable for logging
        return $value;
    }
}
