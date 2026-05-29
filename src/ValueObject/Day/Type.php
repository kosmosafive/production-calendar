<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\ValueObject\Day;

enum Type: int
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
        return match ($this) {
            self::Holiday, self::Weekend => true,
            default => false,
        };
    }
}
