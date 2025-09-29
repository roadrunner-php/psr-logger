<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

use RoadRunner\PsrLogger\Internal\ObjectProcessor;

/**
 * Converts Stringable objects to their string representation.
 *
 * @internal
 *
 * @implements ObjectProcessor<\Stringable>
 */
final class StringableProcessor implements ObjectProcessor
{
    public function canProcess(object $value): bool
    {
        return $value instanceof \Stringable;
    }

    public function process(object $value, callable $processor): mixed
    {
        return (string) $value;
    }
}
