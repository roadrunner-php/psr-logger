<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Converts exceptions to structured data containing class, message,
 * code, file, line, and stack trace information.
 *
 * @implements ObjectProcessor<\Throwable>
 * @api
 */
final class ThrowableProcessor implements ObjectProcessor
{
    #[\Override]
    public function canProcess(object $value): bool
    {
        return $value instanceof \Throwable;
    }

    #[\Override]
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
