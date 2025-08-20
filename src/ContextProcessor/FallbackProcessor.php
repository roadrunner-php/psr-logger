<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Fallback processor for unknown types.
 *
 * Returns the type name for any value that couldn't be processed
 * by more specific processors.
 *
 * @implements ContextProcessorInterface<mixed, string>
 */
class FallbackProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        // This processor can handle anything as a last resort
        return true;
    }

    /**
     * @param callable(mixed): mixed $recursiveProcessor
     * @return string
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        return \gettype($value);
    }
}
