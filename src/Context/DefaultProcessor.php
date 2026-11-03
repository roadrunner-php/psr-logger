<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context;

use RoadRunner\PsrLogger\Context\ObjectProcessor\DateTimeProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor\FallbackProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor\JsonSerializableProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor\StringableProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor\ThrowableProcessor;

/**
 * Default context processor.
 *
 * @api
 */
final class DefaultProcessor
{
    /** @var list<ObjectProcessor> */
    private array $processors = [];

    /** @var positive-int */
    private int $maxDepth = 10;

    /** @var positive-int */
    private int $maxItems = 1000;

    /**
     * Nesting level of the value being processed: object processors call back into this instance,
     * so the level has to live here rather than in a parameter.
     *
     * @var int<0, max>
     */
    private int $depth = 0;

    private function __construct() {}

    /**
     * Create empty instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Create with default set of {@see ObjectProcessor}.
     */
    public static function createDefault(): self
    {
        $self = new self();
        $self->processors = [
            new DateTimeProcessor(),
            // Before StringableProcessor: every \Throwable is also \Stringable
            new ThrowableProcessor(),
            // Before StringableProcessor: structured data reads better in a log than a string
            new JsonSerializableProcessor(),
            new StringableProcessor(),
            new FallbackProcessor(),
        ];
        return $self;
    }

    /**
     * Copy the current object and add Object Processors before existing ones.
     */
    public function withObjectProcessors(ObjectProcessor ...$processors): self
    {
        $clone = clone $this;
        $clone->processors = \array_merge(\array_values($processors), $clone->processors);
        return $clone;
    }

    /**
     * Copy the current object with another limit of nesting levels: a deeper array or object is replaced
     * with a notice, which also ends a reference cycle that no processor cuts.
     *
     * @param int $levels At least 1: the context array itself is the first level.
     *
     * @throws \InvalidArgumentException
     */
    public function withMaxDepth(int $levels): self
    {
        $levels >= 1 or throw new \InvalidArgumentException("Max depth must be at least 1, $levels given.");

        $clone = clone $this;
        $clone->maxDepth = $levels;
        return $clone;
    }

    /**
     * Copy the current object with another limit of items per array: the rest is replaced with a notice
     * under the `...` key.
     *
     * @param int $items At least 1.
     *
     * @throws \InvalidArgumentException
     */
    public function withMaxItems(int $items): self
    {
        $items >= 1 or throw new \InvalidArgumentException("Max items must be at least 1, $items given.");

        $clone = clone $this;
        $clone->maxItems = $items;
        return $clone;
    }

    public function __clone()
    {
        // A copy made by an object processor in the middle of processing would keep that level forever
        $this->depth = 0;
    }

    public function __invoke(mixed $value): mixed
    {
        if (\is_resource($value)) {
            return \get_resource_type($value) . ' resource';
        }

        // JSON has no NaN or infinity
        if (\is_float($value) && !\is_finite($value)) {
            return (string) $value;
        }

        if (!\is_array($value) && !\is_object($value)) {
            return $value;
        }

        if ($this->depth >= $this->maxDepth) {
            return \sprintf('Over %d levels deep, aborting normalization', $this->maxDepth);
        }

        ++$this->depth;
        try {
            return \is_array($value) ? $this->processArray($value) : $this->processObject($value);
        } finally {
            --$this->depth;
        }
    }

    private function processArray(array $value): array
    {
        $result = [];
        $count = 0;
        /** @var mixed $item */
        foreach ($value as $key => $item) {
            if ($count === $this->maxItems) {
                $result['...'] = \sprintf('Over %d items (%d total), aborting normalization', $this->maxItems, \count($value));
                break;
            }

            /** @var mixed */
            $result[$key] = $this($item);
            ++$count;
        }

        return $result;
    }

    private function processObject(object $value): mixed
    {
        foreach ($this->processors as $processor) {
            if ($processor->canProcess($value)) {
                return $processor->process($value, $this);
            }
        }

        return $value;
    }
}
