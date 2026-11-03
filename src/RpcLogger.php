<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger;

use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel as PsrLogLevel;
use RoadRunner\Logger\Logger as AppLogger;
use RoadRunner\Logger\LogLevel;
use RoadRunner\PsrLogger\Context\DefaultProcessor;

/**
 * @api
 */
class RpcLogger implements LoggerInterface
{
    use LoggerTrait;

    private readonly AppLogger $logger;
    private readonly \Closure $processor;

    public function __construct(AppLogger $logger, ?callable $processor = null)
    {
        $this->logger = $logger;
        $this->processor = ($processor ?? DefaultProcessor::createDefault())(...);
    }

    /**
     * @param mixed $level
     * @param array<array-key, mixed> $context
     * @psalm-assert non-empty-string|\Stringable|\BackedEnum|LogLevel $level
     *
     * @link https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel
     */
    #[\Override]
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $normalizedLevel = \strtolower(match (true) {
            \is_string($level) => $level,
            $level instanceof \Stringable => (string) $level,
            $level instanceof \BackedEnum => (string) $level->value,
            $level instanceof LogLevel => $level->name,
            default => throw new PsrInvalidArgumentException('Invalid log level type provided.'),
        });

        // Process context data for structured logging using the processor manager
        /** @var array<string, mixed> $processedContext A processor maps the context array to an array. */
        $processedContext = self::encodable(($this->processor)($context));
        $text = self::scrub((string) $message);

        match ($normalizedLevel) {
            PsrLogLevel::EMERGENCY,
            PsrLogLevel::ALERT,
            PsrLogLevel::CRITICAL,
            PsrLogLevel::ERROR => $this->logger->error($text, $processedContext),
            PsrLogLevel::WARNING => $this->logger->warning($text, $processedContext),
            PsrLogLevel::NOTICE, PsrLogLevel::INFO => $this->logger->info($text, $processedContext),
            'log' => $this->logger->log($text, $processedContext),
            PsrLogLevel::DEBUG => $this->logger->debug($text, $processedContext),
            default => throw new PsrInvalidArgumentException("Invalid log level `$normalizedLevel` provided."),
        };
    }

    /**
     * Makes processed context encodable by app-logger, which silently drops an attribute it fails to encode.
     *
     * Strings and keys get valid UTF-8 for protobuf, non-finite floats become strings for JSON.
     * Objects are left to app-logger.
     *
     * @param int<0, max> $levels Array levels left to descend into: stops on an array that contains
     *        a reference to itself, which app-logger fails to encode anyway.
     */
    private static function encodable(mixed $value, int $levels = 64): mixed
    {
        if (\is_string($value)) {
            return self::scrub($value);
        }

        if (\is_float($value) && !\is_finite($value)) {
            return (string) $value;
        }

        if (!\is_array($value) || $levels === 0) {
            return $value;
        }

        $result = [];
        /** @var mixed $item */
        foreach ($value as $key => $item) {
            /** @var mixed */
            $result[\is_string($key) ? self::scrub($key) : $key] = self::encodable($item, $levels - 1);
        }

        return $result;
    }

    /**
     * Replaces invalid UTF-8 sequences with U+FFFD: protobuf rejects a string that is not valid UTF-8.
     */
    private static function scrub(string $value): string
    {
        // A JSON round trip instead of `mb_scrub()`, which would require ext-mbstring
        return \preg_match('//u', $value) === 1
            ? $value
            : (string) \json_decode(\json_encode($value, \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR));
    }
}
