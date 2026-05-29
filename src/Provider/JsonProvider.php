<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use JsonException;
use RuntimeException;

class JsonProvider implements ProviderInterface
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
    public function getConfiguration(string $country, int $year): array
    {
        $content = $this->getContent($country, $year);
        if ($content === null) {
            return [];
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $this->mapResponse($data, $year);
    }

    /**
     * @throws RuntimeException
     */
    protected function getContent(string $country, int $year): ?string
    {
        $filePath = sprintf('%s/%s_%d.json', $this->directory, $country, $year);

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read file: %s', $filePath));
        }

        return $content;
    }
}
