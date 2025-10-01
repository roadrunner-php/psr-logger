<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context;

use RoadRunner\PsrLogger\Context\ObjectProcessor\DateTimeProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor\FallbackProcessor;
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

    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public static function createDefault(): self
    {
        $self = new self();
        $self->processors = [
            new DateTimeProcessor(),
            new StringableProcessor(),
            new ThrowableProcessor(),
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

    public function __invoke(mixed $value): mixed
    {
        if (\is_resource($value)) {
            return \get_resource_type($value) . ' resource';
        }

        if (\is_array($value)) {
            foreach ($value as &$v) {
                $v = $this($v);
            }
        }

        if (\is_object($value)) {
            foreach ($this->processors as $processor) {
                if ($processor->canProcess($value)) {
                    return $processor->process($value, $this);
                }
            }
        }

        return $value;
    }
}
