<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use Kosmosafive\ProductionCalendar\ValueObject\Holiday;

interface HolidayProviderInterface
{
    /**
     * Возвращает список исключений, где ключ — дата 'Y-m-d'.
     *
     * @return array<string, Holiday>
     */
    public function getHolidays(string $country, int $year): array;
}
