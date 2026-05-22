<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use Closure;

class ClosureProvider implements HolidayProviderInterface
{
    private readonly Closure $closure;

    public function __construct(callable $closure)
    {
        $this->closure = $closure(...);
    }

    public function getHolidays(string $country, int $year): array
    {
        return ($this->closure)($country, $year);
    }
}
