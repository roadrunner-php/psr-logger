<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Processor for objects implementing the Stringable interface.
 *
 * Converts Stringable objects to their string representation.
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
