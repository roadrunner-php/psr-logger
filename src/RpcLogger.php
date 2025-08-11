<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel as PsrLogLevel;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use RoadRunner\Logger\Logger as AppLogger;

class RpcLogger implements LoggerInterface
{
    use LoggerTrait;

    private readonly AppLogger $logger;

    public function __construct(AppLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed $level
     * @param array<array-key, mixed> $context
     *
     * @link https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $normalizedLevel = \is_string($level) ? \strtolower($level) : (string) $level;

        /** @var array<string, mixed> $context */
        switch ($normalizedLevel) {
            case PsrLogLevel::EMERGENCY:
            case PsrLogLevel::ALERT:
            case PsrLogLevel::CRITICAL:
            case PsrLogLevel::ERROR:
                $this->logger->error($message, $context);
                return;

            case PsrLogLevel::WARNING:
                $this->logger->warning($message, $context);
                return;

            case PsrLogLevel::NOTICE:
            case PsrLogLevel::INFO:
                $this->logger->info((string) $message, $context);
                return;

            case PsrLogLevel::DEBUG:
                $this->logger->debug($message, $context);
                return;

            default:
                throw new PsrInvalidArgumentException('Invalid log level: ' . $normalizedLevel);
        }
    }
}
