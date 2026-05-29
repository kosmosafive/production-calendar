<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar;

use DateTimeImmutable;
use Generator;

interface ProductionCalendarInterface
{
    public function isWorkday(DateTimeImmutable $date): bool;

    public function countWorkdays(DateTimeImmutable $start, DateTimeImmutable $end): int;

    public function addWorkdays(DateTimeImmutable $date, int $days): DateTimeImmutable;

    public function subtractWorkdays(DateTimeImmutable $date, int $days): DateTimeImmutable;

    public function getClosestWorkday(DateTimeImmutable $date, bool $forward = true): DateTimeImmutable;

    public function getWorkdaysIterator(DateTimeImmutable $start, DateTimeImmutable $end): Generator;

    public function getFullCalendarIterator(DateTimeImmutable $start, DateTimeImmutable $end): Generator;

    public function hasHolidays(DateTimeImmutable $start, DateTimeImmutable $end): bool;

    public function clearCache(): void;
}
