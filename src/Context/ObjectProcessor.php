<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context;

/**
 * Converts an object into a scalar or an arra for serializable logger context.
 *
 * @template T
 *
 * @internal
 */
interface ObjectProcessor
{
    /**
     * Check if this processor can handle the given value.
     */
    public function canProcess(object $value): bool;

    /**
     * @param T $value
     * @param callable(mixed): mixed $processor Function to process nested object values
     */
    public function process(object $value, callable $processor): mixed;
}
