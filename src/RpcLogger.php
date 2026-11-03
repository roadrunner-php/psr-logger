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
use Testo\Inline\TestInline;

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
        /** @var array<array-key, mixed> $processed A processor maps the context array to an array. */
        $processed = ($this->processor)($context);
        /** @var array<string, mixed> $processedContext app-logger declares string keys, a list context works as well */
        $processedContext = self::encodable($processed);
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
     * @param array<array-key, mixed> $context
     * @return array<array-key, mixed>
     */
    private static function encodable(array $context): array
    {
        $result = [];
        /** @var mixed $value */
        foreach ($context as $key => $value) {
            /** @var mixed */
            $result[\is_string($key) ? self::scrub($key) : $key] = match (true) {
                \is_string($value) => self::scrub($value),
                \is_float($value) && !\is_finite($value) => (string) $value,
                \is_array($value) => self::encodeArray($value),
                default => $value,
            };
        }

        return $result;
    }

    /**
     * Encoded here rather than by app-logger to substitute invalid UTF-8 at any depth. With partial output,
     * what JSON cannot hold (a reference cycle, a non-finite float) is replaced instead of losing the attribute.
     *
     * @param array<array-key, mixed> $value
     */
    private static function encodeArray(array $value): mixed
    {
        try {
            return \json_encode(
                $value,
                \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR,
            );
        } catch (\Throwable) {
            // A throwing jsonSerialize(): app-logger fails on it again and drops just this attribute
            return $value;
        }
    }

    /**
     * Replaces invalid UTF-8 sequences with U+FFFD: protobuf rejects a string that is not valid UTF-8.
     */
    private static function scrub(string $value): string
    {
        return \preg_match('//u', $value) === 1 ? $value : self::substituteInvalidUtf8($value);
    }

    /**
     * `mb_scrub()` when ext-mbstring is loaded, a JSON round trip otherwise: the round trip holds several
     * copies of the string, too many for a large binary value.
     *
     * The extension is checked rather than the function: symfony/polyfill-mbstring defines `mb_scrub()`,
     * which drops invalid bytes, and can empty a string that ends in a cut character.
     *
     * Both give the same result, and CI always has ext-mbstring, so no test can tell them apart.
     *
     * @infection-ignore-all
     */
    private static function substituteInvalidUtf8(string $value): string
    {
        /** @var null|\Closure(string): string $substitute */
        static $substitute = null;
        $substitute ??= \extension_loaded('mbstring') ? self::mbScrub(...) : self::jsonScrub(...);

        return $substitute($value);
    }

    private static function mbScrub(string $value): string
    {
        // mb_scrub() substitutes `?` by default
        $substitute = \mb_substitute_character();
        \mb_substitute_character(0xFFFD);
        try {
            return \mb_scrub($value, 'UTF-8');
        } finally {
            \mb_substitute_character($substitute);
        }
    }

    /**
     * Its flags leave nothing to mutate that a test could tell apart.
     *
     * @infection-ignore-all
     */
    #[TestInline(["v\xFF"], "v\u{FFFD}")]
    #[TestInline(["a\xE2\x82"], "a\u{FFFD}")]
    #[TestInline(['valid ж'], 'valid ж')]
    private static function jsonScrub(string $value): string
    {
        /** @var string $result A JSON string literal decodes to a string. */
        $result = \json_decode(\json_encode($value, \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR));

        return $result;
    }
}
