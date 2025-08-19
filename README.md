# RoadRunner PSR Logger

A PSR-3 compatible logger implementation that integrates with RoadRunner's logging system via RPC calls. This package provides a bridge between PSR-3 logging standards and RoadRunner's centralized logging infrastructure.

## RPC Logger vs STDERR Logger

The RPC logger provides several advantages over RoadRunner's built-in STDERR Logger:

- **Log Level Control**: RPC Logger controls the actual log level sent to RoadRunner server, ensuring proper level filtering and display in RoadRunner logs. Messages from STDERR Logger are processed by RoadRunner with `info` level.
- **Context Support**: RPC logger preserves structured context data (arrays, objects). STDERR Logger outputs only the message string, ignoring context.

## Installation

```bash
composer require roadrunner/psr-logger
```

[![PHP](https://img.shields.io/packagist/php-v/roadrunner/psr-logger.svg?style=flat-square&logo=php)](https://packagist.org/packages/roadrunner/psr-logger)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/roadrunner/psr-logger.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/roadrunner/psr-logger)
[![License](https://img.shields.io/packagist/l/roadrunner/psr-logger.svg?style=flat-square)](LICENSE.md)
[![Total DLoads](https://img.shields.io/packagist/dt/roadrunner/psr-logger.svg?style=flat-square)](https://packagist.org/packages/roadrunner/psr-logger/stats)

## Usage

### Basic Setup

```php
use RoadRunner\Logger\Logger as AppLogger;
use RoadRunner\PsrLogger\RpcLogger;

// Initialize the RoadRunner app logger
$rpc = \Spiral\Goridge\RPC\RPC::create('127.0.0.1:6001');
$appLogger = new AppLogger($rpc);

// Create the PSR-3 compatible logger
$logger = new RpcLogger($appLogger);
```

### Logging Examples

```php
// Basic logging with different levels
$logger->emergency('System is unusable');
$logger->alert('Action must be taken immediately');
$logger->critical('Critical conditions');
$logger->error('Runtime errors');
$logger->warning('Warning conditions');
$logger->notice('Normal but significant condition');
$logger->info('Informational messages');
$logger->debug('Debug-level messages');

// Logging with context data
$logger->info('User logged in', [
    'user_id' => 123,
    'ip_address' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...'
]);

// Using the generic log method
$logger->log(\Psr\Log\LogLevel::ERROR, 'Something went wrong', [
    'exception' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);
```

## Log Levels

### Supported Log Level Types

The logger accepts log levels in multiple formats:
- **String values**: `'error'`, `'warning'`, `'info'`, `'debug'`
- **PSR-3 constants**: `\Psr\Log\LogLevel::ERROR`, `\Psr\Log\LogLevel::WARNING`
- **Stringable objects**: Any object implementing `\Stringable` interface
- **BackedEnum values**: PHP 8.1+ backed enums with string values

```php
// String levels
$logger->log('error', 'Error message');

// PSR-3 constants
$logger->log(\Psr\Log\LogLevel::WARNING, 'Warning message');

// BackedEnum example
enum LogLevel: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
    case Debug = 'debug';
}

$logger->log(LogLevel::Error, 'Error via enum');
```

### Log Level Mapping

The logger maps PSR-3 log levels to RoadRunner logging methods as follows:

| PSR-3 Level | RoadRunner Method |
|-------------|-------------------|
| emergency   | error             |
| alert       | error             |
| critical    | error             |
| error       | error             |
| warning     | warning           |
| notice      | info              |
| info        | info              |
| debug       | debug             |

## Context Handling

The logger supports structured logging with context arrays. When context data is provided, it's passed through to the underlying RoadRunner logger, which can handle complex data structures including:

- Scalar values (string, int, float, bool)
- Arrays and nested arrays
- Objects that implement `\Stringable`
- DateTime objects and exceptions (TODO)

```php
$logger->info('Order processed', [
    'order_id' => 12345,
    'customer' => [
        'id' => 67890,
        'email' => 'customer@example.com'
    ],
    'amount' => 99.99,
    'processed_at' => new \DateTime(),
    'metadata' => [
        'source' => 'web',
        'campaign' => 'summer_sale'
    ]
]);
```
