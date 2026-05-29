<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\ValueObject;

use DateTimeImmutable;
use Kosmosafive\ProductionCalendar\ValueObject\Day\Type;

readonly class CalendarDay
{
    public function __construct(
        public DateTimeImmutable $date,
        public Type $type,
        public bool $isStandardWorkday = true,
        public string $name = '',
        public bool $isStandardWeekend = false,
        public ?DateTimeImmutable $transferredFrom = null
    ) {
    }
}
