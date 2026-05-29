<?php

declare(strict_types=1);

use Kosmosafive\ProductionCalendar\Provider\XmlCalendarProvider;
use Kosmosafive\ProductionCalendar\ValueObject\Day\Type;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

it('fetches and parses calendar data from XML API', function () {
    $client = mock(ClientInterface::class);
    $factory = mock(RequestFactoryInterface::class);
    $response = mock(ResponseInterface::class);
    $stream = mock(StreamInterface::class);

    $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <calendar year="2026" lang="ru" date="2025.09.30" country="ru">
            <holidays>
                <holiday id="1" title="Новогодние каникулы"/>
                <holiday id="2" title="Рождество Христово"/>
                <holiday id="3" title="День защитника Отечества"/>
                <holiday id="4" title="Международный женский день"/>
                <holiday id="5" title="Праздник Весны и Труда"/>
                <holiday id="6" title="День Победы"/>
                <holiday id="7" title="День России"/>
                <holiday id="8" title="День народного единства"/>
            </holidays>
            <days>
                <day d="01.01" t="1" h="1"/>
                <day d="01.02" t="1" h="1"/>
                <day d="01.03" t="1" h="1"/>
                <day d="01.04" t="1" h="1"/>
                <day d="01.05" t="1" h="1"/>
                <day d="01.06" t="1" h="1"/>
                <day d="01.07" t="1" h="2"/>
                <day d="01.08" t="1" h="1"/>
                <day d="01.09" t="1" f="01.03"/>
                <day d="02.23" t="1" h="3"/>
                <day d="03.08" t="1" h="4"/>
                <day d="03.09" t="1" f="03.08"/>
                <day d="04.30" t="2"/>
                <day d="05.01" t="1" h="5"/>
                <day d="05.08" t="2"/>
                <day d="05.09" t="1" h="6"/>
                <day d="05.11" t="1" f="05.09"/>
                <day d="06.11" t="2"/>
                <day d="06.12" t="1" h="7"/>
                <day d="11.03" t="2"/>
                <day d="11.04" t="1" h="8"/>
                <day d="12.31" t="1" f="01.04"/>
            </days>
        </calendar>
        XML;

    $factory->shouldReceive('createRequest')->andReturn(mock(RequestInterface::class));
    $client->shouldReceive('sendRequest')->andReturn($response);
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $response->shouldReceive('getBody')->andReturn($stream);
    $stream->shouldReceive('__toString')->andReturn($xml);

    $provider = new XmlCalendarProvider($client, $factory);
    $holidays = $provider->getConfiguration('ru', 2026);

    expect($holidays)->toBeArray()
        ->and($holidays)->toHaveCount(22)
        ->and($holidays['2026-01-01']->type)->toBe(Type::Holiday)
        ->and($holidays['2026-01-01']->name)->toBe('Новогодние каникулы')
        ->and($holidays['2026-05-08']->type)->toBe(Type::PreHoliday)
        ->and($holidays['2026-01-09']->transferredFrom)->not()->toBeNull()
        ->and($holidays['2026-01-09']->transferredFrom->format('m-d'))->toBe('01-03');
});

it('throws exception on HTTP error', function () {
    $client = mock(ClientInterface::class);
    $factory = mock(RequestFactoryInterface::class);
    $response = mock(ResponseInterface::class);

    $factory->shouldReceive('createRequest')->andReturn(mock(RequestInterface::class));
    $client->shouldReceive('sendRequest')->andReturn($response);
    $response->shouldReceive('getStatusCode')->andReturn(404);

    $provider = new XmlCalendarProvider($client, $factory);

    expect(fn () => $provider->getConfiguration('ru', 2026))
        ->toThrow(RuntimeException::class, 'Could not fetch calendar data for year 2026');
});
