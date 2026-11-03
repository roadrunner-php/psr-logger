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
     */
    private static function encodable(mixed $context): mixed
    {
        if (!\is_array($context)) {
            return $context;
        }

        $result = [];
        /** @var mixed $value */
        foreach ($context as $key => $value) {
            /** @var mixed */
            $result[\is_string($key) ? self::scrub($key) : $key] = match (true) {
                \is_string($value) => self::scrub($value),
                \is_float($value) && !\is_finite($value) => (string) $value,
                // Encoded here rather than by app-logger to substitute invalid UTF-8 at any depth. With partial
                // output a reference cycle becomes `null` and a non-finite float `0` instead of losing the attribute.
                \is_array($value) => \json_encode(
                    $value,
                    \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR,
                ),
                default => $value,
            };
        }

        return $result;
    }

    /**
     * Replaces invalid UTF-8 sequences with U+FFFD: protobuf rejects a string that is not valid UTF-8.
     */
    private static function scrub(string $value): string
    {
        return \preg_match('//u', $value) === 1 ? $value : self::substituteInvalidUtf8($value);
    }

    /**
     * A JSON round trip instead of `mb_scrub()`, which would require ext-mbstring.
     */
    private static function substituteInvalidUtf8(string $value): string
    {
        /** @var string $result A JSON string literal decodes to a string. */
        $result = \json_decode(\json_encode($value, \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR));

        return $result;
    }
}
