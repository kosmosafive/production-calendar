<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\ValueObject;

use DateTimeImmutable;
use Kosmosafive\ProductionCalendar\ValueObject\Day\Type;

readonly class Day
{
    public function __construct(
        public DateTimeImmutable $date,
        public Type $type,
        public string $name = '',
        public ?DateTimeImmutable $transferredFrom = null
    ) {
    }
}
