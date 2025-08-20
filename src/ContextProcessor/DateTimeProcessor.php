<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Processor for DateTime objects.
 *
 * Converts DateTime and DateTimeImmutable objects to ISO 8601 format
 * for consistent structured logging.
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
