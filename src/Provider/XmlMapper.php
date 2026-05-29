<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use DateTimeImmutable;
use Kosmosafive\ProductionCalendar\ValueObject\Day;
use Kosmosafive\ProductionCalendar\ValueObject\Day\Type;
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
    protected function parseXml(Crawler $crawler, int $year): array
    {
        $holidays = [];

        $holidayTitles = [];
        $crawler->filter('holidays holiday')->each(function (Crawler $crawler) use (&$holidayTitles): void {
            $id = $crawler->attr('id');
            $title = $crawler->attr('title');
            if ($id !== null && $title !== null) {
                $holidayTitles[$id] = $title;
            }
        });

        $crawler->filter('days day')->each(function (Crawler $crawler) use (&$holidays, $year, $holidayTitles): void {
            $dayAttr = $crawler->attr('d');
            $typeAttr = $crawler->attr('t');
            $holidayId = $crawler->attr('h');
            $transferredFrom = $crawler->attr('f');

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

    /**
     * @throws RuntimeException
     */
    private function mapDayType(int $type): Type
    {
        return match ($type) {
            1 => Type::Holiday,
            2 => Type::PreHoliday,
            3 => Type::Transferred,
            default => throw new RuntimeException('Unknown day type: ' . $type),
        };
    }
}
