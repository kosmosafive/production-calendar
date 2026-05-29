<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar;

use DateTimeImmutable;
use DateTimeInterface;
use Generator;

interface ProductionCalendarInterface
{
    public function isWorkday(DateTimeInterface $date): bool;
    public function countWorkdays(DateTimeInterface $start, DateTimeInterface $end): int;
    public function addWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable;
    public function subtractWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable;
    public function getClosestWorkday(DateTimeInterface $date, bool $forward = true): DateTimeImmutable;
    public function getWorkdaysIterator(DateTimeInterface $start, DateTimeInterface $end): Generator;
    public function getFullCalendarIterator(DateTimeInterface $start, DateTimeInterface $end): Generator;
    public function hasHolidays(DateTimeInterface $start, DateTimeInterface $end): bool;
}
