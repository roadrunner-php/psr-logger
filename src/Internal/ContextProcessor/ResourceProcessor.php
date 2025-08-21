<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

/**
 * Processor for resource types.
 *
 * Converts resources to string representation indicating the resource type.
 *
 * @internal This class is internal to the PSR Logger implementation and should not be used directly.
 *
 * @implements ContextProcessorInterface<resource, string>
 */
class ResourceProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return \is_resource($value);
    }

    /**
     * @param resource $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return string
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        return \get_resource_type($value) . ' resource';
    }
}
