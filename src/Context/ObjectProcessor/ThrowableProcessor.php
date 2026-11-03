<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor;

/**
 * Converts exceptions to structured data containing class, message, code, file, line, stack trace
 * and the previous exception, if any.
 *
 * @implements ObjectProcessor<\Throwable>
 * @api
 */
final class ThrowableProcessor implements ObjectProcessor
{
    /**
     * @param int $traceFrames Stack frames kept in `trace`, at least 1; the rest is counted in its last line.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        private readonly int $traceFrames = 8,
    ) {
        $traceFrames >= 1 or throw new \InvalidArgumentException("Trace frames must be at least 1, $traceFrames given.");
    }

    #[\Override]
    public function canProcess(object $value): bool
    {
        return $value instanceof \Throwable;
    }

    #[\Override]
    public function process(object $value, callable $processor): array
    {
        $result = [
            'class' => $value::class,
            'message' => $value->getMessage(),
            'code' => $value->getCode(),
            'file' => $value->getFile(),
            'line' => $value->getLine(),
            'trace' => $this->trace($value),
        ];

        $previous = $value->getPrevious();
        if ($previous !== null) {
            /** @var mixed */
            $result['previous'] = $processor($previous);
        }

        return $result;
    }

    /**
     * One line per frame, as in {@see \Throwable::getTraceAsString()} but without arguments: a list would
     * reach RoadRunner as JSON inside a JSON string, escaped twice.
     */
    private function trace(\Throwable $e): string
    {
        $frames = $e->getTrace();
        $lines = [];
        foreach (\array_slice($frames, 0, $this->traceFrames) as $i => $frame) {
            $lines[] = \sprintf(
                '#%d %s: %s%s%s()',
                $i,
                isset($frame['file']) ? $frame['file'] . '(' . ($frame['line'] ?? 0) . ')' : '[internal function]',
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function'] ?? '',
            );
        }

        $rest = \count($frames) - $this->traceFrames;
        $lines[] = $rest > 0 ? \sprintf('... %d more', $rest) : \sprintf('#%d {main}', \count($frames));

        return \implode("\n", $lines);
    }
}
