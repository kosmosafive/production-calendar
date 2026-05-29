<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use RuntimeException;

class XmlProvider implements ProviderInterface
{
    use XmlMapper;

    protected readonly string $directory;

    public function __construct(
        string $directory
    ) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getConfiguration(string $country, int $year): array
    {
        $data = $this->getContent($country, $year);

        return $this->mapResponse($data, $year);
    }

    /**
     * @throws RuntimeException
     */
    protected function getContent(string $country, int $year): string
    {
        $filePath = sprintf('%s/%s_%d.xml', $this->directory, $country, $year);

        if (!file_exists($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to read file: %s', $filePath));
        }

        return $content;
    }
}
