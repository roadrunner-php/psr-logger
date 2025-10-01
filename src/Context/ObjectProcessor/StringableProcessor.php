<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Converts Stringable objects to their string representation.
 *
 * @implements ObjectProcessor<\Stringable>
 * @api
 */
final class StringableProcessor implements ObjectProcessor
{
    #[\Override]
    public function canProcess(object $value): bool
    {
        return $value instanceof \Stringable;
    }

    #[\Override]
    public function process(object $value, callable $processor): mixed
    {
        return (string) $value;
    }
}
