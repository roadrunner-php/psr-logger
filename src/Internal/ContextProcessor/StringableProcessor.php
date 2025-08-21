<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

/**
 * Processor for objects implementing the Stringable interface.
 *
 * Converts Stringable objects to their string representation.
 *
 * @internal This class is internal to the PSR Logger implementation and should not be used directly.
 *
 * @implements ContextProcessorInterface<\Stringable, string>
 */
class StringableProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return $value instanceof \Stringable;
    }

    /**
     * @param \Stringable $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return string
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        return (string) $value;
    }
}
