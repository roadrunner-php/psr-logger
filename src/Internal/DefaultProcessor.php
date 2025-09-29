<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Internal;

use RoadRunner\PsrLogger\Internal\ContextProcessor\DateTimeProcessor;
use RoadRunner\PsrLogger\Internal\ContextProcessor\FallbackProcessor;
use RoadRunner\PsrLogger\Internal\ContextProcessor\StringableProcessor;
use RoadRunner\PsrLogger\Internal\ContextProcessor\ThrowableProcessor;

final class DefaultProcessor
{
    /** @var list<ObjectProcessor> */
    private array $processors = [];

    public static function createDefault(): self
    {
        $self = new self();
        $self->processors = [
            new DateTimeProcessor(),
            new StringableProcessor(),
            new ThrowableProcessor(),
            new FallbackProcessor(),
        ];
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
