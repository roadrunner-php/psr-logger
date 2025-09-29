<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

use RoadRunner\PsrLogger\Internal\ObjectProcessor;

/**
 * Converts exceptions to structured data containing class, message,
 * code, file, line, and stack trace information.
 *
 * @internal
 *
 * @implements ObjectProcessor<\Throwable>
 */
final class ThrowableProcessor implements ObjectProcessor
{
    public function canProcess(object $value): bool
    {
        return $value instanceof \Throwable;
    }

    public function process(object $value, callable $processor): array
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
