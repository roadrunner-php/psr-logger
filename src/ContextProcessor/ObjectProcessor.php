<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\ContextProcessor;

/**
 * Processor for generic objects.
 *
 * Attempts to convert objects to array representation using public properties,
 * or falls back to class name if no public properties are available.
 *
 * @implements ContextProcessorInterface<object, array<string, mixed>|string>
 */
class ObjectProcessor implements ContextProcessorInterface
{
    public function canProcess(mixed $value): bool
    {
        return \is_object($value);
    }

    /**
     * @param object $value
     * @param callable(mixed): mixed $recursiveProcessor
     * @return array<string, mixed>|string
     */
    public function process(mixed $value, callable $recursiveProcessor): mixed
    {
        // Try to convert to array (for objects with public properties)
        $objectVars = \get_object_vars($value);

        if (!empty($objectVars)) {
            /** @var array<string, mixed> $processed */
            $processed = [];
            /**
             * @var string $property
             * @var mixed $propertyValue
             */
            foreach ($objectVars as $property => $propertyValue) {
                /** @psalm-suppress MixedAssignment - Intentionally processing mixed types */
                $processed[$property] = $recursiveProcessor($propertyValue);
            }
            return $processed;
        }

        // Fallback to class name if no public properties
        return \get_class($value);
    }
}
