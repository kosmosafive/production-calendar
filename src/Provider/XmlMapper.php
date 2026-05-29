<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use DateTimeImmutable;
use Kosmosafive\ProductionCalendar\ValueObject\Day;
use Kosmosafive\ProductionCalendar\ValueObject\DayType;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

trait XmlMapper
{
    /**
     * @throws DateMalformedStringException
     */
    protected function mapResponse(string $data, int $year): array
    {
        $crawler = new Crawler($data);

        return $this->parseXml($crawler, $year);
    }

    /**
     * @return array<string, Day>
     *
     * @throws DateMalformedStringException
     */
    private function parseXml(Crawler $crawler, int $year): array
    {
        $holidays = [];

        $holidayTitles = [];
        $crawler->filter('holidays holiday')->each(function (Crawler $node) use (&$holidayTitles) {
            $id = $node->attr('id');
            $title = $node->attr('title');
            if ($id !== null && $title !== null) {
                $holidayTitles[$id] = $title;
            }
        });

        $crawler->filter('days day')->each(function (Crawler $node) use (&$holidays, $year, $holidayTitles) {
            $dayAttr = $node->attr('d');
            $typeAttr = $node->attr('t');
            $holidayId = $node->attr('h');
            $transferredFrom = $node->attr('f');

            if ($dayAttr === null || $typeAttr === null) {
                return;
            }

            $dateStr = sprintf('%d-%s', $year, str_replace('.', '-', $dayAttr));
            $date = new DateTimeImmutable($dateStr);

            $type = $this->mapDayType((int) $typeAttr);

            $name = '';
            $transferredFromDate = null;

            if (($holidayId !== null) && isset($holidayTitles[$holidayId])) {
                $name = $holidayTitles[$holidayId];
            }

            if ($transferredFrom !== null) {
                $transferredDateStr = sprintf('%d-%s', $year, str_replace('.', '-', $transferredFrom));
                $transferredFromDate = new DateTimeImmutable($transferredDateStr);
            }

            $key = $date->format('Y-m-d');
            $holidays[$key] = new Day(
                date: $date,
                type: $type,
                name: $name,
                transferredFrom: $transferredFromDate
            );
        });

        return $holidays;
    }

    private function mapDayType(int $type): DayType
    {
        return match ($type) {
            1 => DayType::Holiday,
            2 => DayType::PreHoliday,
            3 => DayType::Transferred,
            default => throw new RuntimeException('Unknown day type: ' . $type)
        };
    }
}
