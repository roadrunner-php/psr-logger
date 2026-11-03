<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Context;

/**
 * Context processor that runs another one and turns the nested maps of its result into dot-separated
 * keys: `['user' => ['id' => 7]]` becomes `['user.id' => 7]`.
 *
 * An app-logger attribute is a string, so a nested map would reach RoadRunner as one JSON string.
 * An array whose first key is an integer, as a list's is, stays as it is: `tags.0`, `tags.1` would read
 * worse than JSON. When two keys collide, as `a.b` and `['a' => ['b' => …]]` do, the first one wins.
 *
 * @api
 */
final class FlattenProcessor
{
    private readonly \Closure $processor;

    /**
     * @param callable(mixed): mixed $processor Processor of the context, {@see DefaultProcessor} by default.
     */
    public function __construct(?callable $processor = null)
    {
        $this->processor = ($processor ?? DefaultProcessor::createDefault())(...);
    }

    public function __invoke(mixed $context): mixed
    {
        /** @var mixed $processed */
        $processed = ($this->processor)($context);

        return \is_array($processed) ? self::flatten($processed, '') : $processed;
    }

    /**
     * @param array<array-key, mixed> $value
     * @return array<array-key, mixed>
     */
    private static function flatten(array $value, string $prefix): array
    {
        $result = [];
        /** @var mixed $item */
        foreach ($value as $key => $item) {
            $key = $prefix . $key;
            // A list with gaps after array_filter(), or cut by the item limit, still starts with an integer key
            if (\is_array($item) && \is_string(\array_key_first($item))) {
                $result += self::flatten($item, $key . '.');
                continue;
            }

            $result += [$key => $item];
        }

        return $result;
    }
}
