<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

/**
 * Processor for DateTime objects.
 *
 * Converts DateTime and DateTimeImmutable objects to ISO 8601 format
 * for consistent structured logging.
 *
 * @internal This class is internal to the PSR Logger implementation and should not be used directly.
 *
 * @implements ContextProcessorInterface<\DateTimeInterface, string>
 */
class DateTimeProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    /**
     * @param \DateTimeInterface $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return string
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        return $value->format(\DateTimeInterface::ATOM);
    }
}
