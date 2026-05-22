<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\ValueObject;

use DateTimeImmutable;

readonly class CalendarDay
{
    public function __construct(
        public DateTimeImmutable $date,
        public DayType $type,
        public bool $isStandardWorkday = true,
        public string $name = '',
        public bool $isStandardWeekend = false,
        public ?DateTimeImmutable $transferredFrom = null
    ) {
    }
}
