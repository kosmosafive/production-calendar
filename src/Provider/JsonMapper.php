<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateMalformedStringException;
use DateTimeImmutable;
use Kosmosafive\ProductionCalendar\ValueObject\Day;
use Kosmosafive\ProductionCalendar\ValueObject\Day\Type;

trait JsonMapper
{
    /**
     * @param array{months: array<int, array{month: int|string, days: string}>, transitions: array<int, array{from: string, to: string}>} $data
     *
     * @return array<string, Day>
     *
     * @throws DateMalformedStringException
     */
    protected function mapResponse(array $data, int $year): array
    {
        $result = [];

        $transitions = [];
        foreach ($data['transitions'] as $transition) {
            $from = explode('.', (string) $transition['from']);
            $to = explode('.', (string) $transition['to']);

            $dateStringFrom = sprintf('%d-%s-%s', $year, $from[1], $from[0]);
            $dateStringTo = sprintf('%d-%s-%s', $year, $to[1], $to[0]);

            $transitions[$dateStringTo] = new DateTimeImmutable($dateStringFrom);
        }

        foreach ($data['months'] as $month) {
            $days = explode(',', (string) $month['days']);

            foreach ($days as $day) {
                if (str_ends_with($day, '+')) {
                    $dayType = Type::Transferred;
                } elseif (str_ends_with($day, '*')) {
                    $dayType = Type::PreHoliday;
                } else {
                    $dayType = Type::Holiday;
                }

                $dateString = sprintf(
                    '%d-%s-%s',
                    $year,
                    str_pad((string) $month['month'], 2, '0', STR_PAD_LEFT),
                    str_pad(rtrim($day, '*+'), 2, '0', STR_PAD_LEFT)
                );

                $result[$dateString] = new Day(
                    date: new DateTimeImmutable($dateString),
                    type: $dayType,
                    transferredFrom: $transitions[$dateString] ?? null
                );
            }
        }

        return $result;
    }
}
