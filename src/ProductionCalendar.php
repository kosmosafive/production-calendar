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
use Kosmosafive\ProductionCalendar\Provider\ProviderInterface;
use Kosmosafive\ProductionCalendar\ValueObject\CalendarDay;
use Kosmosafive\ProductionCalendar\ValueObject\DayType;

class ProductionCalendar implements ProductionCalendarInterface
{
    private const string DATE_FORMAT = 'Y-m-d';
    private const string YEAR_FORMAT = 'Y';
    private array $cache = [];

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly string $country
    ) {
        if (!preg_match('/^[a-z]{2}$/', $this->country)) {
            throw new InvalidArgumentException('Country code must be 2 lowercase letters');
        }
    }

    public function isWorkday(DateTimeInterface $date): bool
    {
        $year = (int) $date->format(static::YEAR_FORMAT);
        $dateKey = $date->format(static::DATE_FORMAT);

        if (!isset($this->cache[$year])) {
            $this->cache[$year] = $this->provider->getConfiguration($this->country, $year);
        }

        if (isset($this->cache[$year][$dateKey])) {
            return $this->cache[$year][$dateKey]->type->isWorking();
        }

        $dayOfWeek = (int) $date->format('N');
        return $dayOfWeek <= 5;
    }

    /**
     * @throws DateMalformedPeriodStringException
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
     */
    protected function createDatePeriod(DateTimeInterface $start, DateTimeInterface $end): DatePeriod
    {
        if ($end < $start) {
            throw new DateMalformedPeriodStringException('End date must be after start date');
        }

        $endDate = clone $end;
        return new DatePeriod(
            $start,
            new DateInterval('P1D'),
            $endDate->modify('+1 day')
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function addWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable
    {
        $currentDate = DateTimeImmutable::createFromInterface($date);
        $step = $days > 0 ? 1 : -1;
        $remaining = abs($days);

        while ($remaining > 0) {
            $currentDate = $currentDate->modify($step . ' day');
            if ($this->isWorkday($currentDate)) {
                $remaining--;
            }
        }

        return $currentDate;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function subtractWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable
    {
        return $this->addWorkdays($date, -$days);
    }

    /**
     * @return Generator<DateTimeImmutable>
     *
     * @throws DateMalformedPeriodStringException
     */
    public function getWorkdaysIterator(DateTimeInterface $start, DateTimeInterface $end): Generator
    {
        $datePeriod = $this->createDatePeriod($start, $end);

        foreach ($datePeriod as $date) {
            if ($this->isWorkday($date)) {
                yield DateTimeImmutable::createFromInterface($date);
            }
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getClosestWorkday(DateTimeInterface $date, bool $forward = true): DateTimeImmutable
    {
        $currentDate = DateTimeImmutable::createFromInterface($date);
        $step = $forward ? 1 : -1;

        while (!$this->isWorkday($currentDate)) {
            $currentDate = $currentDate->modify($step . ' day');
        }

        return $currentDate;
    }

    /**
     * @return Generator<CalendarDay>
     *
     * @throws DateMalformedPeriodStringException
     */
    public function getFullCalendarIterator(DateTimeInterface $start, DateTimeInterface $end): Generator
    {
        $datePeriod = $this->createDatePeriod($start, $end);

        foreach ($datePeriod as $date) {
            $year = (int) $date->format(static::YEAR_FORMAT);
            $dateKey = $date->format(static::DATE_FORMAT);
            $dayOfWeek = (int) $date->format('N');
            $isWeekend = $dayOfWeek >= 6;

            if (!isset($this->cache[$year])) {
                $this->cache[$year] = $this->provider->getConfiguration($this->country, $year);
            }

            $holiday = $this->cache[$year][$dateKey] ?? null;

            if ($holiday) {
                $type = $holiday->type;
                $isStandardWorkday = $type->equals(DayType::PreHoliday);
                $name = $holiday->name;
            } else {
                $type = $isWeekend ? DayType::Weekend : DayType::Working;
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
     */
    public function hasHolidays(DateTimeInterface $start, DateTimeInterface $end): bool
    {
        foreach ($this->getWorkdaysIterator($start, $end) as $date) {
            if (!$this->isWorkday($date)) {
                return true;
            }
        }
        return false;
    }
}
