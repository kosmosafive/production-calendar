<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar;

use DateInterval;
use DateMalformedPeriodStringException;
use DateMalformedStringException;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use Kosmosafive\ProductionCalendar\Cache\LimitedInMemoryCache;
use Kosmosafive\ProductionCalendar\Provider\ProviderInterface;
use Kosmosafive\ProductionCalendar\ValueObject\CalendarDay;
use Kosmosafive\ProductionCalendar\ValueObject\Day\Type;
use Psr\SimpleCache\CacheInterface;

class ProductionCalendar implements ProductionCalendarInterface
{
    private const string DATE_FORMAT = 'Y-m-d';

    private const string YEAR_FORMAT = 'Y';

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly string $country,
        private readonly CacheInterface $cache = new LimitedInMemoryCache()
    ) {
        if (!preg_match('/^[a-z]{2}$/', $this->country)) {
            throw new InvalidArgumentException('Country code must be 2 lowercase letters');
        }
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function isWorkday(DateTimeInterface $date): bool
    {
        $year = (int) $date->format(self::YEAR_FORMAT);
        $dateKey = $date->format(self::DATE_FORMAT);

        $yearData = $this->getYearData($year);

        if (isset($yearData[$dateKey])) {
            return $yearData[$dateKey]->type->isWorking();
        }

        return (int) $date->format('N') <= 5;
    }

    /**
     * @throws DateMalformedPeriodStringException
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function countWorkdays(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $count = 0;
        $datePeriod = $this->createDatePeriod($start, $end);

        foreach ($datePeriod as $date) {
            if ($this->isWorkday($date)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @throws DateMalformedPeriodStringException
     * @throws DateMalformedStringException
     */
    protected function createDatePeriod(DateTimeInterface $start, DateTimeInterface $end): DatePeriod
    {
        if ($end < $start) {
            throw new DateMalformedPeriodStringException('End date must be after start date');
        }

        if (!($end instanceof DateTimeImmutable)) {
            $end = DateTimeImmutable::createFromInterface($end);
        }

        return new DatePeriod(
            $start,
            new DateInterval('P1D'),
            $end->modify('+1 day')
        );
    }

    /**
     * Добавляет рабочие дни к дате. Отрицательное значение $days
     * эквивалентно вызову subtractWorkdays().
     *
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable
    {
        if ($days === 0) {
            return DateTimeImmutable::createFromInterface($date);
        }

        $currentDate = DateTimeImmutable::createFromInterface($date);
        $step = $days > 0 ? 1 : -1;
        $remaining = abs($days);

        while ($remaining > 0) {
            $currentDate = $currentDate->modify($step . ' day');
            if ($this->isWorkday($currentDate)) {
                --$remaining;
            }
        }

        return $currentDate;
    }

    /**
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function subtractWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable
    {
        return $this->addWorkdays($date, -$days);
    }

    /**
     * @return Generator<DateTimeImmutable>
     *
     * @throws DateMalformedPeriodStringException
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getWorkdaysIterator(DateTimeInterface $start, DateTimeInterface $end): Generator
    {
        foreach ($this->createDatePeriod($start, $end) as $date) {
            if ($this->isWorkday($date)) {
                yield DateTimeImmutable::createFromInterface($date);
            }
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getClosestWorkday(DateTimeInterface $date, bool $forward = true): DateTimeImmutable
    {
        if ($this->isWorkday($date)) {
            return DateTimeImmutable::createFromInterface($date);
        }

        $currentDate = DateTimeImmutable::createFromInterface($date);
        $step = $forward ? 1 : -1;

        do {
            $currentDate = $currentDate->modify($step . ' day');
        } while (!$this->isWorkday($currentDate));

        return $currentDate;
    }

    /**
     * @return Generator<CalendarDay>
     *
     * @throws DateMalformedPeriodStringException
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getFullCalendarIterator(DateTimeInterface $start, DateTimeInterface $end): Generator
    {
        foreach ($this->createDatePeriod($start, $end) as $date) {
            $year = (int) $date->format(self::YEAR_FORMAT);
            $dateKey = $date->format(self::DATE_FORMAT);
            $dayOfWeek = (int) $date->format('N');
            $isWeekend = $dayOfWeek >= 6;

            $yearData = $this->getYearData($year);
            $holiday = $yearData[$dateKey] ?? null;

            if ($holiday !== null) {
                $type = $holiday->type;
                $isStandardWorkday = $type->equals(Type::PreHoliday);
                $name = $holiday->name;
            } else {
                $type = $isWeekend ? Type::Weekend : Type::Working;
                $isStandardWorkday = !$isWeekend;
                $name = '';
            }

            yield new CalendarDay(
                date: DateTimeImmutable::createFromInterface($date),
                type: $type,
                isStandardWorkday: $isStandardWorkday,
                name: $name,
                isStandardWeekend: $isWeekend,
                transferredFrom: $holiday?->transferredFrom
            );
        }
    }

    /**
     * @throws DateMalformedPeriodStringException
     * @throws DateMalformedStringException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function hasHolidays(DateTimeInterface $start, DateTimeInterface $end): bool
    {
        foreach ($this->getFullCalendarIterator($start, $end) as $date) {
            if ($date->type->equals(Type::Holiday)) {
                return true;
            }
        }

        return false;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getYearData(int $year): array
    {
        $cacheKey = (string) $year;

        if (!$this->cache->has($cacheKey)) {
            $this->cache->set($cacheKey, $this->provider->getConfiguration($this->country, $year));
        }

        return $this->cache->get($cacheKey);
    }
}
