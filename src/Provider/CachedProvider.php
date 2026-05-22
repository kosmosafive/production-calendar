<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CachedProvider implements HolidayProviderInterface
{
    public function __construct(
        private readonly HolidayProviderInterface $wrapped,
        private readonly CacheInterface $cache,
        private readonly int $ttl = 86400
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getHolidays(string $country, int $year): array
    {
        $cacheKey = sprintf('holiday_calendar_%s_%d', $country, $year);

        $cachedData = $this->cache->get($cacheKey);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $holidays = $this->wrapped->getHolidays($country, $year);

        $this->cache->set($cacheKey, $holidays, $this->ttl);

        return $holidays;
    }
}
