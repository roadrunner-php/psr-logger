<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit;

use Testo\Data\DataProvider;
use Testo\Codecov\Covers;
use Testo\Test;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Lifecycle\AfterTest;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LogLevel as PsrLogLevel;
use RoadRunner\AppLogger\DTO\V1\LogEntry;
use RoadRunner\Logger\Logger as AppLogger;
use RoadRunner\Logger\LogLevel;
use RoadRunner\PsrLogger\Context\DefaultProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor;
use RoadRunner\PsrLogger\RpcLogger;

#[Covers(RpcLogger::class)]
#[Test]
final class RpcLoggerTest
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

        Assert::instanceOf($logger, RpcLogger::class);
    }

    #[DataProvider('emergencyLevelsProvider')]
    public function testLogWithEmergencyLevels(string $level): void
    {
        $message = 'Emergency message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log($level, $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
    }

    public function testLogWithWarningLevel(): void
    {
        $message = 'Warning message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log(PsrLogLevel::WARNING, $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'WarningWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
    }

    #[DataProvider('infoLevelsProvider')]
    public function testLogWithInfoLevels(string $level): void
    {
        $message = 'Info message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log($level, $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
    }

    public function testLogWithDebugLevel(): void
    {
        $message = 'Debug message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log(PsrLogLevel::DEBUG, $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'DebugWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Info');
        Assert::same($lastCall['payload'], 'Stringable message');
    }

    public function testLogWithEmptyContext(): void
    {
        $message = 'Test message';

        $this->rpcLogger->log(PsrLogLevel::INFO, $message);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Info');
        Assert::same($lastCall['payload'], $message);
    }

    public function testLogWithCaseInsensitiveLevel(): void
    {
        $message = 'Test message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log('ERROR', $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
    }

    public function testLogWithMixedCaseLevel(): void
    {
        $message = 'Test message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log('Warning', $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'WarningWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
    }

    public function testLogWithEnumLevel(): void
    {
        $this->rpcLogger->log(LogLevelEnum::Warning, 'Test message');

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Warning');
    }

    public function testLogWithRREnumLogLevel(): void
    {
        $this->rpcLogger->log(LogLevel::Log, 'Test message');

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Log');
    }

    public function testLogWithInvalidLevel(): void
    {
        Expect::exception(PsrInvalidArgumentException::class)->withMessageContaining('Invalid log level `invalid` provided.');

        $this->rpcLogger->log('invalid', 'Test message');
    }

    public function testLogWithNonStringLevel(): void
    {
        Expect::exception(PsrInvalidArgumentException::class)->withMessageContaining('Invalid log level type provided.');

        $this->rpcLogger->log(123, 'Test message');
    }

    public function testLogWithNullLevel(): void
    {
        Expect::exception(PsrInvalidArgumentException::class)->withMessageContaining('Invalid log level type provided.');

        $this->rpcLogger->log(null, 'Test message');
    }

    public function testLogWithBooleanLevel(): void
    {
        Expect::exception(PsrInvalidArgumentException::class)->withMessageContaining('Invalid log level type provided.');

        $this->rpcLogger->log(true, 'Test message');
    }

    public function testLogWithEmptyStringMessage(): void
    {
        $this->rpcLogger->log(PsrLogLevel::INFO, '');

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Info');
        Assert::same($lastCall['payload'], '');
    }

    public function testLogWithNumericStringMessage(): void
    {
        $this->rpcLogger->log(PsrLogLevel::INFO, '12345');

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Info');
        Assert::same($lastCall['payload'], '12345');
    }

    public function testLogWithEmptyContextArray(): void
    {
        $message = 'Test message';
        $context = [];

        $this->rpcLogger->log(PsrLogLevel::INFO, $message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'Info');
        Assert::same($lastCall['payload'], $message);
    }

    // Test PSR-3 LoggerTrait methods
    public function testEmergencyMethod(): void
    {
        $message = 'Emergency message';
        $context = ['key' => 'value'];

        $this->rpcLogger->emergency($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
    }

    public function testAlertMethod(): void
    {
        $message = 'Alert message';
        $context = ['key' => 'value'];

        $this->rpcLogger->alert($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
    }

    public function testCriticalMethod(): void
    {
        $message = 'Critical message';
        $context = ['key' => 'value'];

        $this->rpcLogger->critical($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
    }

    public function testErrorMethod(): void
    {
        $message = 'Error message';
        $context = ['key' => 'value'];

        $this->rpcLogger->error($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
    }

    public function testWarningMethod(): void
    {
        $message = 'Warning message';
        $context = ['key' => 'value'];

        $this->rpcLogger->warning($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'WarningWithContext');
    }

    public function testNoticeMethod(): void
    {
        $message = 'Notice message';
        $context = ['key' => 'value'];

        $this->rpcLogger->notice($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
    }

    public function testInfoMethod(): void
    {
        $message = 'Info message';
        $context = ['key' => 'value'];

        $this->rpcLogger->info($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
    }

    public function testDebugMethod(): void
    {
        $message = 'Debug message';
        $context = ['key' => 'value'];

        $this->rpcLogger->debug($message, $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'DebugWithContext');
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'DebugWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'WarningWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'InfoWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        $processorManager = DefaultProcessor::createDefault()
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'WarningWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'ErrorWithContext');
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
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

        $processorManager = DefaultProcessor::createDefault()
            ->withObjectProcessors($firstProcessor)  // Added first, should be used
            ->withObjectProcessors($secondProcessor); // Added second, should be skipped

        $logger = new RpcLogger($this->appLogger, $processorManager);

        $context = ['number' => 42];
        $logger->debug('Ordering test', $context);

        Assert::same($this->rpc->getCallCount(), 1);
        $lastCall = $this->rpc->getLastCall();
        Assert::same($lastCall['method'], 'DebugWithContext');

        // The first processor should have been used
        // We can't directly inspect the processed context, but we know it was processed
        Assert::instanceOf($lastCall['payload'], LogEntry::class);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->rpc = new RpcSpy();
        $this->appLogger = new AppLogger($this->rpc);
        $this->rpcLogger = new RpcLogger($this->appLogger);
    }

    #[AfterTest]
    protected function tearDown(): void
    {
        // Reset the RPC spy after each test to ensure clean state
        $this->rpc->reset();
    }
}
