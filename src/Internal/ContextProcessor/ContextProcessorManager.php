<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal\ContextProcessor;

/**
 * Manager for context data processors.
 *
 * Coordinates multiple processors to handle different data types for structured logging.
 * Processors are executed in registration order, with the first matching processor handling the value.
 *
 * @internal This class is internal to the PSR Logger implementation and should not be used directly.
 */
class ContextProcessorManager
{
    /** @var array<ContextProcessorInterface> */
    private array $processors = [];

    public function __construct()
    {
        $this->registerDefaultProcessors();
    }

    /**
     * Register a processor.
     * Processors are checked in the order they are added.
     */
    public function addProcessor(ContextProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * Process context data recursively.
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $context
     * @return array<string, mixed>
     */
    public function processContext(array $context): array
    {
        if (empty($context)) {
            return [];
        }

        /** @var array<string, mixed> $processed */
        $processed = [];

        /**
         * @var TKey $key
         * @var TValue $value
         */
        foreach ($context as $key => $value) {
            $stringKey = (string) $key;
            /** @psalm-suppress MixedAssignment - Intentionally processing mixed types */
            $processed[$stringKey] = $this->processValue($value);
        }

        return $processed;
    }

    /**
     * Process a single value using the appropriate processor.
     */
    public function processValue(mixed $value): mixed
    {
        foreach ($this->processors as $processor) {
            if ($processor->canProcess($value)) {
                return $processor->process($value, [$this, 'processValue']);
            }
        }

        // This should never happen due to FallbackProcessor, but just in case
        return \gettype($value);
    }

    /**
     * Register the default set of processors in the correct order.
     * Order matters: more specific processors should be registered first.
     */
    private function registerDefaultProcessors(): void
    {
        // Null values first (most specific)
        $this->addProcessor(new NullProcessor());

        // Scalar values (very common, but after null)
        $this->addProcessor(new ScalarProcessor());

        // Specific object types (before generic object processor)
        $this->addProcessor(new DateTimeProcessor());
        $this->addProcessor(new ThrowableProcessor());
        $this->addProcessor(new StringableProcessor());

        // Collections and resources
        $this->addProcessor(new ArrayProcessor());
        $this->addProcessor(new ResourceProcessor());

        // Generic object processor (before fallback)
        $this->addProcessor(new ObjectProcessor());

        // Fallback processor (last resort)
        $this->addProcessor(new FallbackProcessor());
    }
}
