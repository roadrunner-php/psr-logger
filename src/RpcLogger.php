<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel as PsrLogLevel;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
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
    private readonly \Closure $objectProcessor;

    public function __construct(AppLogger $logger, ?callable $processor = null)
    {
        $this->logger = $logger;
        $this->objectProcessor = ($processor ?? DefaultProcessor::createDefault())(...);
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
        $processedContext = ($this->objectProcessor)($context);

        match ($normalizedLevel) {
            PsrLogLevel::EMERGENCY,
            PsrLogLevel::ALERT,
            PsrLogLevel::CRITICAL,
            PsrLogLevel::ERROR => $this->logger->error($message, $processedContext),
            PsrLogLevel::WARNING => $this->logger->warning($message, $processedContext),
            PsrLogLevel::NOTICE, PsrLogLevel::INFO => $this->logger->info((string) $message, $processedContext),
            'log' => $this->logger->log((string) $message, $processedContext),
            PsrLogLevel::DEBUG => $this->logger->debug($message, $processedContext),
            default => throw new PsrInvalidArgumentException("Invalid log level `$normalizedLevel` provided."),
        };
    }
}
