<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Processor for Exception and Throwable objects.
 *
 * Converts exceptions to structured data containing class, message,
 * code, file, line, and stack trace information.
 *
 * @implements ContextProcessorInterface<\Throwable, array<string, mixed>>
 */
class ThrowableProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return $value instanceof \Throwable;
    }

    /**
     * @param \Throwable $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return array<string, mixed>
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        return [
            'class' => \get_class($value),
            'message' => $value->getMessage(),
            'code' => $value->getCode(),
            'file' => $value->getFile(),
            'line' => $value->getLine(),
            'trace' => $value->getTraceAsString(),
        ];
    }
}
