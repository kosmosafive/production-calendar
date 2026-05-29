<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use Kosmosafive\ProductionCalendar\ValueObject\Day;

interface ProviderInterface
{
    /**
     * Возвращает список исключений, где ключ — дата 'Y-m-d'.
     *
     * @return array<string, Day>
     */
    public function getConfiguration(string $country, int $year): array;
}
