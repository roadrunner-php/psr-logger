<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Fallback processor for unknown objects.
 *
 * @implements ObjectProcessor<object>
 * @api
 */
final class FallbackProcessor implements ObjectProcessor
{
    #[\Override]
    public function canProcess(object $value): bool
    {
        return true;
    }

    #[\Override]
    public function process(object $value, callable $processor): array
    {
        $result = ['@class' => $value::class] + \get_object_vars($value);
        foreach ($result as $k => &$v) {
            if ($v === $value) {
                unset($result[$k]);
            }

            $v = $processor($v);
        }

        return $result;
    }
}
