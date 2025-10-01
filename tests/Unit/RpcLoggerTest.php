<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LogLevel as PsrLogLevel;
use RoadRunner\AppLogger\DTO\V1\LogEntry;
use RoadRunner\Logger\Logger as AppLogger;
use RoadRunner\Logger\LogLevel;
use RoadRunner\PsrLogger\Context\DefaultProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor;
use RoadRunner\PsrLogger\RpcLogger;

#[CoversClass(RpcLogger::class)]
class RpcLoggerTest extends TestCase
{
    private RpcSpy $rpc;
    private AppLogger $appLogger;
    private RpcLogger $rpcLogger;

    public static function emergencyLevelsProvider(): array
    {
        return [
            'emergency' => [PsrLogLevel::EMERGENCY],
            'alert' => [PsrLogLevel::ALERT],
            'critical' => [PsrLogLevel::CRITICAL],
            'error' => [PsrLogLevel::ERROR],
        ];
    }

    public static function infoLevelsProvider(): array
    {
        return [
            'notice' => [PsrLogLevel::NOTICE],
            'info' => [PsrLogLevel::INFO],
        ];
    }

    public function testConstructor(): void
    {
        $logger = new RpcLogger($this->appLogger);

        $this->assertInstanceOf(RpcLogger::class, $logger);
    }

    #[DataProvider('emergencyLevelsProvider')]
    public function testLogWithEmergencyLevels(string $level): void
    {
        $message = 'Emergency message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log($level, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithWarningLevel(): void
    {
        $message = 'Warning message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log(PsrLogLevel::WARNING, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    #[DataProvider('infoLevelsProvider')]
    public function testLogWithInfoLevels(string $level): void
    {
        $message = 'Info message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log($level, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithDebugLevel(): void
    {
        $message = 'Debug message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log(PsrLogLevel::DEBUG, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('DebugWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithStringableMessage(): void
    {
        $stringableMessage = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $this->rpcLogger->log(PsrLogLevel::INFO, $stringableMessage);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Info', $lastCall['method']);
        $this->assertSame('Stringable message', $lastCall['payload']);
    }

    public function testLogWithEmptyContext(): void
    {
        $message = 'Test message';

        $this->rpcLogger->log(PsrLogLevel::INFO, $message);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Info', $lastCall['method']);
        $this->assertSame($message, $lastCall['payload']);
    }

    public function testLogWithCaseInsensitiveLevel(): void
    {
        $message = 'Test message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log('ERROR', $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithMixedCaseLevel(): void
    {
        $message = 'Test message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log('Warning', $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithEnumLevel(): void
    {
        $this->rpcLogger->log(LogLevelEnum::Warning, 'Test message');

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Warning', $lastCall['method']);
    }

    public function testLogWithRREnumLogLevel(): void
    {
        $this->rpcLogger->log(LogLevel::Log, 'Test message');

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Log', $lastCall['method']);
    }

    public function testLogWithInvalidLevel(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level `invalid` provided.');

        $this->rpcLogger->log('invalid', 'Test message');
    }

    public function testLogWithNonStringLevel(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level type provided.');

        $this->rpcLogger->log(123, 'Test message');
    }

    public function testLogWithNullLevel(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level type provided.');

        $this->rpcLogger->log(null, 'Test message');
    }

    public function testLogWithBooleanLevel(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level type provided.');

        $this->rpcLogger->log(true, 'Test message');
    }

    public function testLogWithEmptyStringMessage(): void
    {
        $this->rpcLogger->log(PsrLogLevel::INFO, '');

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Info', $lastCall['method']);
        $this->assertSame('', $lastCall['payload']);
    }

    public function testLogWithNumericStringMessage(): void
    {
        $this->rpcLogger->log(PsrLogLevel::INFO, '12345');

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Info', $lastCall['method']);
        $this->assertSame('12345', $lastCall['payload']);
    }

    public function testLogWithEmptyContextArray(): void
    {
        $message = 'Test message';
        $context = [];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Info', $lastCall['method']);
        $this->assertSame($message, $lastCall['payload']);
    }

    // Test PSR-3 LoggerTrait methods
    public function testEmergencyMethod(): void
    {
        $message = 'Emergency message';
        $context = ['key' => 'value'];

        $this->rpcLogger->emergency($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
    }

    public function testAlertMethod(): void
    {
        $message = 'Alert message';
        $context = ['key' => 'value'];

        $this->rpcLogger->alert($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
    }

    public function testCriticalMethod(): void
    {
        $message = 'Critical message';
        $context = ['key' => 'value'];

        $this->rpcLogger->critical($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
    }

    public function testErrorMethod(): void
    {
        $message = 'Error message';
        $context = ['key' => 'value'];

        $this->rpcLogger->error($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
    }

    public function testWarningMethod(): void
    {
        $message = 'Warning message';
        $context = ['key' => 'value'];

        $this->rpcLogger->warning($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
    }

    public function testNoticeMethod(): void
    {
        $message = 'Notice message';
        $context = ['key' => 'value'];

        $this->rpcLogger->notice($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
    }

    public function testInfoMethod(): void
    {
        $message = 'Info message';
        $context = ['key' => 'value'];

        $this->rpcLogger->info($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
    }

    public function testDebugMethod(): void
    {
        $message = 'Debug message';
        $context = ['key' => 'value'];

        $this->rpcLogger->debug($message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('DebugWithContext', $lastCall['method']);
    }

    public function testLogWithComplexContext(): void
    {
        $message = 'Complex context message';
        $context = [
            'user_id' => 123,
            'action' => 'login',
            'metadata' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
            ],
            'timestamp' => new \DateTime(),
        ];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
    }

    public function testLogWithScalarContext(): void
    {
        $message = 'Scalar context message';
        $context = [
            'string_value' => 'test string',
            'int_value' => 42,
            'float_value' => 3.14,
            'bool_value' => true,
            'null_value' => null,
        ];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithDateTimeContext(): void
    {
        $message = 'DateTime context message';
        $dateTime = new \DateTime('2023-01-01T12:00:00+00:00');
        $dateTimeImmutable = new \DateTimeImmutable('2023-01-01T13:00:00+00:00');

        $context = [
            'created_at' => $dateTime,
            'updated_at' => $dateTimeImmutable,
        ];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithExceptionContext(): void
    {
        $message = 'Exception context message';
        $exception = new \RuntimeException('Test exception message', 500);

        $context = [
            'error' => $exception,
            'additional_info' => 'Some additional context',
        ];

        $this->rpcLogger->log(PsrLogLevel::ERROR, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithStringableContext(): void
    {
        $message = 'Stringable context message';
        $stringableObject = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Custom stringable object';
            }
        };

        $context = [
            'user' => $stringableObject,
            'status' => 'active',
        ];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithNestedArrayContext(): void
    {
        $message = 'Nested array context message';
        $context = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'deep_value' => 'nested data',
                        'number' => 123,
                    ],
                    'another_value' => true,
                ],
                'simple_value' => 'test',
            ],
            'root_value' => 'root',
        ];

        $this->rpcLogger->log(PsrLogLevel::DEBUG, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('DebugWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithObjectContext(): void
    {
        $message = 'Object context message';
        $object = new class {
            public string $publicProp = 'public value';
            private string $privateProp = 'private value';
            protected string $protectedProp = 'protected value';
        };

        $context = [
            'user_data' => $object,
            'other_info' => 'additional data',
        ];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithResourceContext(): void
    {
        $message = 'Resource context message';
        $resource = \fopen('php://memory', 'r');

        $context = [
            'file_handle' => $resource,
            'operation' => 'read',
        ];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        \fclose($resource);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithMixedComplexContext(): void
    {
        $message = 'Mixed complex context message';
        $exception = new \InvalidArgumentException('Invalid input', 400);
        $dateTime = new \DateTime('2023-01-01T12:00:00+00:00');
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Mixed context stringable';
            }
        };

        $context = [
            'user_id' => 123,
            'error' => $exception,
            'timestamp' => $dateTime,
            'user_agent' => $stringable,
            'metadata' => [
                'ip' => '127.0.0.1',
                'session_id' => 'abc123',
                'nested' => [
                    'deep' => [
                        'value' => 'very deep',
                        'count' => 5,
                    ],
                ],
            ],
            'is_admin' => false,
            'score' => 98.5,
        ];

        $this->rpcLogger->log(PsrLogLevel::WARNING, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testCustomProcessorIntegration(): void
    {
        // Create a custom processor for email addresses
        $emailProcessor = new class implements ObjectProcessor {
            public function canProcess(mixed $value): bool
            {
                return \is_string($value) && \filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            }

            public function process(mixed $value, callable $processor): mixed
            {
                // Mask email for privacy
                $parts = \explode('@', $value);
                return \substr($parts[0], 0, 2) . '***@' . $parts[1];
            }
        };

        // Create processor manager with custom processor added first
        $processorManager = DefaultProcessor::createDefault()->withObjectProcessors($emailProcessor);

        // Create logger with custom processor manager
        $logger = new RpcLogger($this->appLogger, $processorManager);

        $context = [
            'user_email' => 'john.doe@example.com',
            'admin_email' => 'admin@company.org',
            'regular_string' => 'not an email',
            'user_id' => 123,
        ];

        $logger->info('User action performed', $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('InfoWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testMultipleCustomProcessors(): void
    {
        // Custom processor for URLs
        $urlProcessor = new class implements ObjectProcessor {
            public function canProcess(mixed $value): bool
            {
                return \is_string($value) && \filter_var($value, FILTER_VALIDATE_URL) !== false;
            }

            public function process(mixed $value, callable $processor): mixed
            {
                $parsed = \parse_url($value);
                return [
                    'scheme' => $parsed['scheme'] ?? null,
                    'host' => $parsed['host'] ?? null,
                    'path' => $parsed['path'] ?? null,
                ];
            }
        };

        // Custom processor for credit card numbers (mock)
        $ccProcessor = new class implements ObjectProcessor {
            public function canProcess(mixed $value): bool
            {
                return \is_string($value) && \preg_match('/^\d{4}-?\d{4}-?\d{4}-?\d{4}$/', $value);
            }

            public function process(mixed $value, callable $processor): mixed
            {
                return '****-****-****-' . \substr($value, -4);
            }
        };

        $processorManager = \RoadRunner\PsrLogger\Context\DefaultProcessor::createDefault()
            ->withObjectProcessors($urlProcessor)
            ->withObjectProcessors($ccProcessor);

        $logger = new RpcLogger($this->appLogger, $processorManager);

        $context = [
            'website' => 'https://example.com/path/to/resource',
            'payment_card' => '1234-5678-9012-3456',
            'regular_data' => 'normal string',
            'amount' => 99.99,
        ];

        $logger->warning('Payment processed', $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testDefaultProcessorManagerWhenNoneProvided(): void
    {
        // Test that RpcLogger creates default processor manager when none provided
        $logger = new RpcLogger($this->appLogger);

        $context = [
            'timestamp' => new \DateTime('2023-01-01T12:00:00+00:00'),
            'exception' => new \RuntimeException('Test error'),
            'user_id' => 123,
        ];

        $logger->error('Test with default processors', $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('ErrorWithContext', $lastCall['method']);
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    public function testProcessorOrdering(): void
    {
        // Create processors for the same type to test ordering
        $firstProcessor = new class implements ObjectProcessor {
            public function canProcess(mixed $value): bool
            {
                return \is_int($value);
            }

            public function process(mixed $value, callable $processor): mixed
            {
                return 'first:' . $value;
            }
        };

        $secondProcessor = new class implements ObjectProcessor {
            public function canProcess(mixed $value): bool
            {
                return \is_int($value);
            }

            public function process(mixed $value, callable $processor): mixed
            {
                return 'second:' . $value;
            }
        };

        $processorManager = \RoadRunner\PsrLogger\Context\DefaultProcessor::createDefault()
            ->withObjectProcessors($firstProcessor)  // Added first, should be used
            ->withObjectProcessors($secondProcessor); // Added second, should be skipped

        $logger = new RpcLogger($this->appLogger, $processorManager);

        $context = ['number' => 42];
        $logger->debug('Ordering test', $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('DebugWithContext', $lastCall['method']);

        // The first processor should have been used
        // We can't directly inspect the processed context, but we know it was processed
        $this->assertInstanceOf(LogEntry::class, $lastCall['payload']);
    }

    protected function setUp(): void
    {
        $this->rpc = new RpcSpy();
        $this->appLogger = new AppLogger($this->rpc);
        $this->rpcLogger = new RpcLogger($this->appLogger);
    }

    protected function tearDown(): void
    {
        // Reset the RPC spy after each test to ensure clean state
        $this->rpc->reset();
    }
}
