<?php

declare(strict_types=1);

namespace Kosmosafive\ProductionCalendar\Provider;

use DateTimeImmutable;
use Kosmosafive\ProductionCalendar\ValueObject\DayType;
use Kosmosafive\ProductionCalendar\ValueObject\Holiday;

trait JsonMapper
{
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
                if (str_ends_with('+', $day)) {
                    $dayType = DayType::Transferred;
                } elseif (str_ends_with('*', $day)) {
                    $dayType = DayType::PreHoliday;
                } else {
                    $dayType = DayType::Holiday;
                }

                $dateString = sprintf(
                    '%d-%s-%s',
                    $year,
                    str_pad((string) $month['month'], 2, '0', STR_PAD_LEFT),
                    str_pad((string) (int) $day, 2, '0', STR_PAD_LEFT)
                );

                $result[$dateString] = new Holiday(
                    date: new DateTimeImmutable($dateString),
                    type: $dayType,
                    transferredFrom: $transitions[$dateString] ?? null
                );
            }
        }

        return $result;
    }
}
