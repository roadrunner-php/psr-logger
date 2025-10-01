<?php

declare(strict_types=1);

namespace Context\ObjectProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Context\ObjectProcessor\DateTimeProcessor;

#[CoversClass(DateTimeProcessor::class)]
class DateTimeProcessorTest extends TestCase
{
    private DateTimeProcessor $processor;

    public function testCanProcessDateTime(): void
    {
        $dateTime = new \DateTime();
        $this->assertTrue($this->processor->canProcess($dateTime));
    }

    public function testCanProcessDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable();
        $this->assertTrue($this->processor->canProcess($dateTime));
    }

    public function testCannotProcessNonDateTime(): void
    {
        $this->assertFalse($this->processor->canProcess(new \stdClass()));
    }

    public function testProcessDateTime(): void
    {
        $dateTime = new \DateTime('2023-01-01T12:00:00+00:00');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($dateTime, $recursiveProcessor);

        $this->assertSame('2023-01-01T12:00:00+00:00', $result);
    }

    public function testProcessDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2023-06-15T09:30:00+02:00');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($dateTime, $recursiveProcessor);

        $this->assertSame('2023-06-15T09:30:00+02:00', $result);
    }

    protected function setUp(): void
    {
        $this->processor = new DateTimeProcessor();
    }
}
