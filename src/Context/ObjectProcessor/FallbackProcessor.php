<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Fallback processor for unknown objects.
 *
 * Exports the class name and public properties. A property that references an object still being
 * exported (the object itself or one of its owners) is left out, and an object reached again through
 * an array or another processor is exported as its class name only, so reference cycles terminate.
 *
 * @implements ObjectProcessor<object>
 * @api
 */
final class FallbackProcessor implements ObjectProcessor
{
    /** @var \SplObjectStorage<object, null> Objects whose export is in progress. */
    private readonly \SplObjectStorage $exporting;

    public function __construct()
    {
        $this->exporting = new \SplObjectStorage();
    }

    #[\Override]
    public function canProcess(object $value): bool
    {
        return true;
    }

    #[\Override]
    public function process(object $value, callable $processor): array
    {
        if ($this->exporting->contains($value)) {
            return ['@class' => $value::class];
        }

        $properties = [];
        $this->exporting->attach($value);
        try {
            /** @var mixed $property */
            foreach (\get_object_vars($value) as $name => $property) {
                if (\is_object($property) && $this->exporting->contains($property)) {
                    continue;
                }

                /** @var mixed */
                $properties[$name] = $processor($property);
            }
        } finally {
            $this->exporting->detach($value);
        }

        return ['@class' => $value::class] + $properties;
    }
}
