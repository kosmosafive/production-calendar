<?php

declare(strict_types=1);

use Kosmosafive\ProductionCalendar\Cache\LimitedInMemoryCache;

beforeEach(function () {
    $this->cache = new LimitedInMemoryCache(limit: 5);
});

describe('constructor', function () {
    it('throws InvalidArgumentException when limit is less than 1', function () {
        new LimitedInMemoryCache(limit: 0);
    })->throws(InvalidArgumentException::class);

    it('initializes correctly with limit = 1', function () {
        $cache = new LimitedInMemoryCache(limit: 1);
        $cache->set('a', 1);
        $cache->set('b', 2);

        expect($cache->has('a'))->toBeFalse()
            ->and($cache->get('b'))->toBe(2);
    });
});

describe('get, set & has', function () {
    it('stores and retrieves a value', function () {
        $this->cache->set('key', 'value');
        expect($this->cache->get('key'))->toBe('value')
            ->and($this->cache->has('key'))->toBeTrue();
    });

    it('returns default value for missing keys', function () {
        expect($this->cache->get('missing', 'default'))->toBe('default')
            ->and($this->cache->has('missing'))->toBeFalse();
    });

    it('always returns true on set', function () {
        expect($this->cache->set('key', 'value'))->toBeTrue();
    });

    it('does not overwrite existing values or change queue order', function () {
        $this->cache->set('key', 'original');
        $this->cache->set('key', 'updated');

        expect($this->cache->get('key'))->toBe('original');
    });
});

describe('limit enforcement (FIFO)', function () {
    it('evicts oldest items when limit is exceeded', function () {
        $cache = new LimitedInMemoryCache(limit: 3);
        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', 3);
        $cache->set('d', 4); // должен вытеснить 'a'

        expect($cache->has('a'))->toBeFalse()
            ->and($cache->has('b'))->toBeTrue()
            ->and($cache->get('b'))->toBe(2)
            ->and($cache->get('d'))->toBe(4);
    });

    it('maintains correct eviction order after multiple insertions', function () {
        $cache = new LimitedInMemoryCache(limit: 2);
        for ($i = 1; $i <= 5; $i++) {
            $cache->set("key{$i}", $i);
        }

        expect($cache->has('key1'))->toBeFalse()
            ->and($cache->has('key2'))->toBeFalse()
            ->and($cache->has('key4'))->toBeTrue()
            ->and($cache->get('key5'))->toBe(5);
    });
});

describe('delete', function () {
    it('removes existing key and returns true', function () {
        $this->cache->set('key', 'val');
        expect($this->cache->delete('key'))->toBeTrue()
            ->and($this->cache->has('key'))->toBeFalse();
    });

    it('returns false for non-existing key', function () {
        expect($this->cache->delete('nonexistent'))->toBeFalse();
    });

    it('correctly adjusts internal queue when deleting a middle item', function () {
        $cache = new LimitedInMemoryCache(limit: 3);
        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', 3);

        $cache->delete('b'); // удаляем средний элемент
        $cache->set('dd', 4);
        $cache->set('d', 4); // должен вытеснить 'a' как самый старый

        expect($cache->has('a'))->toBeFalse()
            ->and($cache->has('c'))->toBeTrue()
            ->and($cache->get('d'))->toBe(4);
    });
});

describe('clear', function () {
    it('removes all stored data and returns true', function () {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->cache->clear();

        expect($this->cache->clear())->toBeTrue()
            ->and($this->cache->has('a'))->toBeFalse()
            ->and($this->cache->has('b'))->toBeFalse()
            ->and($this->cache->get('a'))->toBeNull();
    });

    it('allows reuse after clearing', function () {
        $this->cache->set('old', 'data');
        $this->cache->clear();
        $this->cache->set('new', 'value');

        expect($this->cache->get('new'))->toBe('value');
    });
});

describe('multiple methods', function () {
    it('getMultiple returns values and defaults as iterable', function () {
        $this->cache->set('a', 1);
        $result = $this->cache->getMultiple(['a', 'b', 'c'], 'default');

        expect($result)->toBeArray()
            ->and($result['a'])->toBe(1)
            ->and($result['b'])->toBe('default')
            ->and($result['c'])->toBe('default');
    });

    it('setMultiple stores values and respects limit', function () {
        $cache = new LimitedInMemoryCache(limit: 2);
        expect($cache->setMultiple(['x' => 10, 'y' => 20, 'z' => 30]))->toBeTrue();

        expect($cache->has('x'))->toBeFalse()
            ->and($cache->get('y'))->toBe(20)
            ->and($cache->get('z'))->toBe(30);
    });

    it('deleteMultiple removes specified keys and returns true', function () {
        $this->cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);
        expect($this->cache->deleteMultiple(['a', 'c']))->toBeTrue()
            ->and($this->cache->has('a'))->toBeFalse()
            ->and($this->cache->has('c'))->toBeFalse()
            ->and($this->cache->has('b'))->toBeTrue();
    });

    it('casts non-string keys to strings in multiple methods', function () {
        $this->cache->setMultiple([123 => 'int', 3 => 'float']);

        expect($this->cache->get('123'))->toBe('int')
            ->and($this->cache->get('3'))->toBe('float');
    });
});

describe('ttl handling', function () {
    it('ignores ttl parameter without throwing errors', function () {
        expect($this->cache->set('key', 'val', 3600))->toBeTrue()
            ->and($this->cache->set('key2', 'val2', new DateInterval('PT1H')))->toBeTrue()
            ->and($this->cache->get('key'))->toBe('val')
            ->and($this->cache->get('key2'))->toBe('val2');
    });
});
