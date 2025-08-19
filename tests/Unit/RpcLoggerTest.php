<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LogLevel as PsrLogLevel;
use RoadRunner\Logger\Logger as AppLogger;
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
        $this->assertInstanceOf(\RoadRunner\AppLogger\DTO\V1\LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithWarningLevel(): void
    {
        $message = 'Warning message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log(PsrLogLevel::WARNING, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
        $this->assertInstanceOf(\RoadRunner\AppLogger\DTO\V1\LogEntry::class, $lastCall['payload']);
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
        $this->assertInstanceOf(\RoadRunner\AppLogger\DTO\V1\LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithDebugLevel(): void
    {
        $message = 'Debug message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log(PsrLogLevel::DEBUG, $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('DebugWithContext', $lastCall['method']);
        $this->assertInstanceOf(\RoadRunner\AppLogger\DTO\V1\LogEntry::class, $lastCall['payload']);
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
        $this->assertInstanceOf(\RoadRunner\AppLogger\DTO\V1\LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithMixedCaseLevel(): void
    {
        $message = 'Test message';
        $context = ['key' => 'value'];

        $this->rpcLogger->log('Warning', $message, $context);

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('WarningWithContext', $lastCall['method']);
        $this->assertInstanceOf(\RoadRunner\AppLogger\DTO\V1\LogEntry::class, $lastCall['payload']);
    }

    public function testLogWithEnumLevel(): void
    {
        $this->rpcLogger->log(LogLevelEnum::Warning, 'Test message');

        $this->assertSame(1, $this->rpc->getCallCount());
        $lastCall = $this->rpc->getLastCall();
        $this->assertSame('Warning', $lastCall['method']);
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
        $this->expectExceptionMessage('Invalid log level `123` provided.');

        $this->rpcLogger->log(123, 'Test message');
    }

    public function testLogWithNullLevel(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level `` provided.');

        $this->rpcLogger->log(null, 'Test message');
    }

    public function testLogWithBooleanLevel(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level `1` provided.');

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

    protected function setUp(): void
    {
        $this->rpc = new RpcSpy();
        $this->appLogger = new AppLogger($this->rpc);
        $this->rpcLogger = new RpcLogger($this->appLogger);
    }
}
