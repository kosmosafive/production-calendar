<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use RuntimeException;

class XmlCalendarProvider implements HolidayProviderInterface
{
    use JsonMapper;

    protected const string URI_FORMAT = 'https://xmlcalendar.ru/data/%s/%d/calendar.json';

    public function __construct(
        protected readonly ClientInterface $httpClient,
        protected readonly RequestFactoryInterface $requestFactory
    ) {
    }

    /**
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function getHolidays(string $country, int $year): array
    {
        $uri = sprintf(static::URI_FORMAT, $country, $year);
        $request = $this->requestFactory
            ->createRequest('GET', $uri);

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(
                sprintf('Could not fetch calendar data for year %d (HTTP code: ', $year) . $response->getStatusCode() . ")"
            );
        }

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return $this->mapResponse($data, $year);
    }
}
