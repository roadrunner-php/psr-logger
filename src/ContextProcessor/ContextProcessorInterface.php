<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Interface for context data processors.
 *
 * Each processor handles a specific type of data and converts it to a
 * format suitable for structured logging.
 *
 * @template TValue The input value type
 * @template TProcessed The processed output type
 */
interface ContextProcessorInterface
{
    /**
     * Check if this processor can handle the given value.
     */
    public function canProcess(mixed $value): bool;

    /**
     * Process the value and return a serializable representation.
     *
     * @param TValue $value The value to process
     * @param callable(mixed): mixed $recursiveProcessor Function to process nested values recursively
     * @return TProcessed Processed value suitable for logging
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed;
}
