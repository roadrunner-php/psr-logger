<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Processor for DateTime objects.
 *
 * Converts DateTime and DateTimeImmutable objects to ISO 8601 format
 * for consistent structured logging.
 *
 * @implements ObjectProcessor<\DateTimeInterface>
 * @api
 */
final class DateTimeProcessor implements ObjectProcessor
{
    #[\Override]
    public function canProcess(object $value): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    #[\Override]
    public function process(object $value, callable $processor): mixed
    {
        return $value->format(\DateTimeInterface::ATOM);
    }
}
