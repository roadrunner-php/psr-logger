<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class RpcLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        /**
         * TODO: to be implemented
         *
         * @link https://github.com/roadrunner-php/psr-logger/issues/3
         */
    }
}
