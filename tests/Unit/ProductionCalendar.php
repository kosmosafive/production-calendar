<?php

declare(strict_types=1);

use Kosmosafive\ProductionCalendar\ProductionCalendar;
use Kosmosafive\ProductionCalendar\Provider\HolidayProviderInterface;
use Kosmosafive\ProductionCalendar\ValueObject\DayType;
use Kosmosafive\ProductionCalendar\ValueObject\Holiday;

beforeEach(function () {
    $this->provider = mock(HolidayProviderInterface::class);

    $this->provider->shouldReceive('getHolidays')->andReturn([
        '2026-01-01' => new Holiday(new DateTimeImmutable('2026-01-01'), DayType::Holiday, 'New Year'),
        '2026-01-03' => new Holiday(new DateTimeImmutable('2026-01-03'), DayType::Transferred),
    ]);

    $this->calendar = new ProductionCalendar($this->provider, 'ru');
});

it('correctly identifies workdays and holidays', function () {
    expect($this->calendar->isWorkday(new DateTime('2026-01-01')))->toBeFalse()
        ->and($this->calendar->isWorkday(new DateTime('2026-01-02')))->toBeTrue()
        ->and($this->calendar->isWorkday(new DateTime('2026-01-03')))->toBeTrue()
        ->and($this->calendar->isWorkday(new DateTime('2026-01-04')))->toBeFalse();
});

it('calculates workdays count in range', function () {
    $start = new DateTime('2026-01-01');
    $end = new DateTime('2026-01-05');

    expect($this->calendar->countWorkdays($start, $end))->toBe(3);
});

it('adds workdays correctly', function () {
    $start = new DateTime('2025-12-31');

    $result = $this->calendar->addWorkdays($start, 1);

    expect($result->format('Y-m-d'))->toBe('2026-01-02');
});

it('returns full info for every day in period', function () {
    $start = new DateTime('2026-01-01');
    $end = new DateTime('2026-01-01');

    $iterator = $this->calendar->getFullCalendarIterator($start, $end);
    $days = iterator_to_array($iterator);

    expect($days)->toHaveCount(1)
        ->and($days[0]->date->format('Y-m-d'))->toBe('2026-01-01')
        ->and($days[0]->type->isWorking())->toBeFalse()
        ->and($days[0]->name)->toBe('New Year');
});
