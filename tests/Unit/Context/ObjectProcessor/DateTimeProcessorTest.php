<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use Testo\Codecov\Covers;
use Testo\Test;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use RoadRunner\PsrLogger\Context\ObjectProcessor\DateTimeProcessor;

#[Covers(DateTimeProcessor::class)]
#[Test]
final class DateTimeProcessorTest
{
    private DateTimeProcessor $processor;

    public function testCanProcessDateTime(): void
    {
        $dateTime = new \DateTime();
        Assert::true($this->processor->canProcess($dateTime));
    }

    public function testCanProcessDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable();
        Assert::true($this->processor->canProcess($dateTime));
    }

    public function testCannotProcessNonDateTime(): void
    {
        Assert::false($this->processor->canProcess(new \stdClass()));
    }

    public function testProcessDateTime(): void
    {
        $dateTime = new \DateTime('2023-01-01T12:00:00+00:00');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($dateTime, $recursiveProcessor);

        Assert::same($result, '2023-01-01T12:00:00+00:00');
    }

    public function testProcessDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2023-06-15T09:30:00+02:00');
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($dateTime, $recursiveProcessor);

        Assert::same($result, '2023-06-15T09:30:00+02:00');
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = new DateTimeProcessor();
    }
}
