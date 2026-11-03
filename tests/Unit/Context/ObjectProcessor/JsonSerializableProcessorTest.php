<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor\JsonSerializableProcessor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Covers(JsonSerializableProcessor::class)]
#[Test]
final class JsonSerializableProcessorTest
{
    public function acceptsJsonSerializable(): void
    {
        Assert::true((new JsonSerializableProcessor())->canProcess(self::serializable(['id' => 1])));
    }

    public function rejectsOtherObjects(): void
    {
        Assert::false((new JsonSerializableProcessor())->canProcess(new \stdClass()));
    }

    public function passesSerializedDataToRecursiveProcessor(): void
    {
        $result = (new JsonSerializableProcessor())->process(
            self::serializable(['id' => 1]),
            static fn(mixed $data): array => ['processed' => $data],
        );

        Assert::same($result, ['processed' => ['id' => 1]]);
    }

    private static function serializable(mixed $data): \JsonSerializable
    {
        return new class($data) implements \JsonSerializable {
            public function __construct(
                private readonly mixed $data,
            ) {}

            public function jsonSerialize(): mixed
            {
                return $this->data;
            }
        };
    }
}
