<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use JsonException;

class JsonProvider implements HolidayProviderInterface
{
    use JsonMapper;

    protected readonly string $directory;

    public function __construct(
        string $directory
    ) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    /**
     * @throws JsonException
     */
    public function getHolidays(string $country, int $year): array
    {
        $filePath = sprintf('%s/%s_%d.json', $this->directory, $country, $year);

        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $this->mapResponse($data, $year);
    }
}
