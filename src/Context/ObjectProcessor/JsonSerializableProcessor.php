<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Converts JsonSerializable objects to the data their `jsonSerialize()` returns.
 *
 * @implements ObjectProcessor<\JsonSerializable>
 * @api
 */
final class JsonSerializableProcessor implements ObjectProcessor
{
    #[\Override]
    public function canProcess(object $value): bool
    {
        return $value instanceof \JsonSerializable;
    }

    #[\Override]
    public function process(object $value, callable $processor): mixed
    {
        // The data may hold objects of its own
        return $processor($value->jsonSerialize());
    }
}
