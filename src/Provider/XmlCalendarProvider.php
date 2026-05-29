<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use DateTimeImmutable;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class XmlCalendarProvider implements HolidayProviderInterface
{
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
    public function getHolidays(string $country, int $year): array
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

        $xmlContent = (string) $response->getBody();
        $crawler = new Crawler($xmlContent);

        return $this->parseXml($crawler, $year);
    }

    /**
     * @return array<string, \Kosmosafive\ProductionCalendar\ValueObject\Holiday>
     * @throws DateMalformedStringException
     */
    private function parseXml(Crawler $crawler, int $year): array
    {
        $holidays = [];

        // Парсим справочник праздников (id => title)
        $holidayTitles = [];
        $crawler->filter('holidays holiday')->each(function (Crawler $node) use (&$holidayTitles) {
            $id = $node->attr('id');
            $title = $node->attr('title');
            if ($id !== null && $title !== null) {
                $holidayTitles[$id] = $title;
            }
        });

        // Парсим дни
        $crawler->filter('days day')->each(function (Crawler $node) use (&$holidays, $year, $holidayTitles) {
            $dayAttr = $node->attr('d');
            $typeAttr = $node->attr('t');
            $holidayId = $node->attr('h');
            $transferredFrom = $node->attr('f');

            if ($dayAttr === null || $typeAttr === null) {
                return;
            }

            // Формируем дату в формате Y-m-d
            $dateStr = sprintf('%d-%s', $year, str_replace('.', '-', $dayAttr));
            $date = new DateTimeImmutable($dateStr);

            // Определяем тип дня
            $type = $this->mapDayType((int) $typeAttr);

            // Если это праздник или предпраздничный день
            $name = '';
            $transferredFromDate = null;

            if ($holidayId !== null && isset($holidayTitles[$holidayId])) {
                $name = $holidayTitles[$holidayId];
            }

            // Если есть перенос (атрибут f)
            if ($transferredFrom !== null) {
                $transferredDateStr = sprintf('%d-%s', $year, str_replace('.', '-', $transferredFrom));
                $transferredFromDate = new DateTimeImmutable($transferredDateStr);
            }

            // Создаем объект Holiday только для нерабочих дней и особых случаев
            if ($type->isNonWorking() || $type === DayType::PreHoliday || $type === DayType::Transferred || $transferredFromDate !== null) {
                $key = $date->format('Y-m-d');
                $holidays[$key] = new \Kosmosafive\ProductionCalendar\ValueObject\Holiday(
                    date: $date,
                    type: $type,
                    name: $name,
                    transferredFrom: $transferredFromDate
                );
            }
        });

        return $holidays;
    }

    /**
     * Маппинг типа дня из XML в DayType
     * t="1" - выходной/праздник
     * t="2" - сокращенный рабочий день (предпраздничный)
     * t="3" - рабочий субботний день (перенос)
     * t="4" - рабочий воскресный день (перенос)
     * t="5" - обычный рабочий день
     * t="6" - обычный рабочий день
     */
    private function mapDayType(int $type): DayType
    {
        return match ($type) {
            1 => DayType::Holiday,      // Выходной или праздник
            2 => DayType::PreHoliday,   // Сокращенный день
            3, 4 => DayType::Transferred, // Перенесенный рабочий день
            5, 6 => DayType::Working,   // Обычный рабочий день
            default => DayType::Working,
        };
    }
}
