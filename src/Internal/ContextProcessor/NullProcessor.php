<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

/**
 * Processor for null values.
 *
 * Handles null values explicitly, passing them through as-is
 * since null is already suitable for structured logging.
 *
 * @internal This class is internal to the PSR Logger implementation and should not be used directly.
 *
 * @implements ContextProcessorInterface<null, null>
 */
class NullProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return $value === null;
    }

    /**
     * @param null $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return null
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        // Null values are already suitable for logging
        return null;
    }
}
