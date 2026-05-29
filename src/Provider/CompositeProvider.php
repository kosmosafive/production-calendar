<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

class CompositeProvider implements ProviderInterface
{
    /** @var ProviderInterface[] */
    private readonly array $providers;

    public function __construct(ProviderInterface ...$providers)
    {
        $this->providers = $providers;
    }

    public function getConfiguration(string $country, int $year): array
    {
        foreach ($this->providers as $provider) {
            $providerResult = $provider->getConfiguration($country, $year);
            if ($providerResult !== []) {
                return $providerResult;
            }
        }

        return [];
    }
}
