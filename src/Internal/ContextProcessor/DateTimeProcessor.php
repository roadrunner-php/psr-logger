<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

use RoadRunner\PsrLogger\Internal\ObjectProcessor;

/**
 * Processor for DateTime objects.
 *
 * Converts DateTime and DateTimeImmutable objects to ISO 8601 format
 * for consistent structured logging.
 *
 * @implements ObjectProcessor<\DateTimeInterface>
 *
 * @internal
 */
final class DateTimeProcessor implements ObjectProcessor
{
    public function canProcess(object $value): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function process(object $value, callable $processor): mixed
    {
        return $value->format(\DateTimeInterface::ATOM);
    }
}
