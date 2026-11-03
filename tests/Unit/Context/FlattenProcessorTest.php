<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context;

use RoadRunner\PsrLogger\Context\DefaultProcessor;
use RoadRunner\PsrLogger\Context\FlattenProcessor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Covers(FlattenProcessor::class)]
#[Test]
final class FlattenProcessorTest
{
    public function turnsNestedMapsIntoDotKeys(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): mixed => $v);

        $result = $processor(['user' => ['id' => 7, 'address' => ['city' => 'Paris']], 'ok' => true]);

        Assert::same($result, ['user.id' => 7, 'user.address.city' => 'Paris', 'ok' => true]);
    }

    public function keepsListsAndEmptyArrays(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): mixed => $v);

        $result = $processor(['tags' => ['a', 'b'], 'items' => [['id' => 1]], 'none' => []]);

        Assert::same($result, ['tags' => ['a', 'b'], 'items' => [['id' => 1]], 'none' => []]);
    }

    public function keepsArraysStartingWithIntegerKey(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): mixed => $v);

        $result = $processor(['tags' => \array_filter(['a', '', 'c']), 'ids' => [1 => 'x', 'y' => 'z']]);

        Assert::same($result, ['tags' => [0 => 'a', 2 => 'c'], 'ids' => [1 => 'x', 'y' => 'z']]);
    }

    public function keepsListCutByItemLimit(): void
    {
        $processor = new FlattenProcessor(DefaultProcessor::create()->withMaxItems(2));

        Assert::same($processor(['ids' => [1, 2, 3]]), [
            'ids' => [1, 2, '...' => 'Over 2 items (3 total), aborting normalization'],
        ]);
    }

    public function flattensMapWithIntegerKeysAfterFirst(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): mixed => $v);

        Assert::same($processor(['m' => ['a' => 1, 5 => 'x']]), ['m.a' => 1, 'm.5' => 'x']);
    }

    public function keepsFirstOfCollidingKeys(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): mixed => $v);

        Assert::same($processor(['a.b' => 1, 'a' => ['b' => 2, 'c' => 3]]), ['a.b' => 1, 'a.c' => 3]);
        Assert::same($processor(['a' => ['b' => 2], 'a.b' => 1]), ['a.b' => 2]);
    }

    public function flattensResultOfInnerProcessor(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): array => ['wrapped' => ['value' => $v]]);

        Assert::same($processor('x'), ['wrapped.value' => 'x']);
    }

    public function returnsNonArrayResultAsIs(): void
    {
        $processor = new FlattenProcessor(static fn(mixed $v): string => 'scalar');

        Assert::same($processor(['a' => ['b' => 1]]), 'scalar');
    }

    public function usesDefaultProcessorWithoutArgument(): void
    {
        $result = (new FlattenProcessor())([
            'exception' => new \RuntimeException('boom', 3),
            'at' => new \DateTimeImmutable('2024-02-03T04:05:06+00:00'),
        ]);

        Assert::same($result['exception.class'], \RuntimeException::class);
        Assert::same($result['exception.message'], 'boom');
        Assert::same($result['exception.code'], 3);
        Assert::same($result['at'], '2024-02-03T04:05:06+00:00');
    }
}
