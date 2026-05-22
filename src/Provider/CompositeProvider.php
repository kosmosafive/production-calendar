<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

class CompositeProvider implements HolidayProviderInterface
{
    /** @var HolidayProviderInterface[] */
    private readonly array $providers;

    public function __construct(HolidayProviderInterface ...$providers)
    {
        $this->providers = $providers;
    }

    public function getHolidays(string $country, int $year): array
    {
        foreach ($this->providers as $provider) {
            $providerResult = $provider->getHolidays($country, $year);
            if (!empty($providerResult)) {
                return $providerResult;
            }
        }

        return [];
    }
}
