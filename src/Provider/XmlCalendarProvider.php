<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use RuntimeException;

class XmlCalendarProvider implements ProviderInterface
{
    use XmlMapper;
    protected const string URI_FORMAT = 'https://xmlcalendar.ru/data/%s/%d/calendar.xml';

    public function __construct(
        protected readonly ClientInterface $httpClient,
        protected readonly RequestFactoryInterface $requestFactory
    ) {
    }

    /**
     * @throws DateMalformedStringException
     * @throws ClientExceptionInterface
     */
    public function getConfiguration(string $country, int $year): array
    {
        $uri = sprintf(static::URI_FORMAT, $country, $year);
        $request = $this->requestFactory
            ->createRequest('GET', $uri);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(
                sprintf('Could not fetch calendar data for year %d (HTTP code: %d)', $year, $response->getStatusCode())
            );
        }

        $data = (string) $response->getBody();

        return $this->mapResponse($data, $year);
    }
}
