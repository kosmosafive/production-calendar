<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\ValueObject;

enum DayType: int
{
    case Holiday = 1;
    case PreHoliday = 2;
    case Transferred = 3;
    case Working = 4;
    case Weekend = 5;

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isWorking(): bool
    {
        return !$this->isNonWorking();
    }

    public function isNonWorking(): bool
    {
        return ($this === self::Holiday) || ($this === self::Weekend);
    }
}
