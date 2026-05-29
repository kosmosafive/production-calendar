<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Cache;

use DateInterval;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use SplQueue;

class LimitedInMemoryCache implements CacheInterface
{
    private SplQueue $queue;
    private array $data = [];

    public function __construct(
        private readonly int $limit = 5
    ) {
        if ($this->limit < 1) {
            throw new InvalidArgumentException('Limit must be at least 1');
        }

        $this->queue = new SplQueue();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if (!array_key_exists($key, $this->data)) {
            $this->queue->enqueue($key);
            $this->data[$key] = $value;

            if ($this->queue->count() > $this->limit) {
                $oldestKey = $this->queue->dequeue();
                unset($this->data[$oldestKey]);
            }
        }

        return true;
    }

    public function delete(string $key): bool
    {
        if (!array_key_exists($key, $this->data)) {
            return false;
        }

        $indexToRemove = null;

        foreach ($this->queue as $index => $value) {
            if ($value === $key) {
                $indexToRemove = $index;
                break;
            }
        }

        if ($indexToRemove !== null) {
            $this->queue->offsetUnset($indexToRemove);
        }

        unset($this->data[$key]);
    }

    public function clear(): bool
    {
        $this->queue = new SplQueue();
        $this->data = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $this->get($key, $default);
        }
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}
