<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\ValueObject;

use DateTimeImmutable;

readonly class Day
{
    public function __construct(
        public DateTimeImmutable $date,
        public DayType $type,
        public string $name = '',
        public ?DateTimeImmutable $transferredFrom = null
    ) {
    }
}
