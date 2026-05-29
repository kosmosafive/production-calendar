<?php

declare(strict_types=1);

use Kosmosafive\ProductionCalendar\Provider\JsonProvider;
use Kosmosafive\ProductionCalendar\ValueObject\DayType;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

it('fetches and parses calendar data from API', function () {
    $client = mock(ClientInterface::class);
    $factory = mock(RequestFactoryInterface::class);
    $response = mock(ResponseInterface::class);
    $stream = mock(StreamInterface::class);
    $provider = mock(JsonProvider::class)->makePartial();
    $provider->shouldAllowMockingProtectedMethods();

    $json = '{"year":2026,"months":[{"month":1,"days":"1,2,3,4,5,6,7,8,9+,10,11,17,18,24,25,31"},{"month":2,"days":"1,7,8,14,15,21,22,23,28"},{"month":3,"days":"1,7,8,9+,14,15,21,22,28,29"},{"month":4,"days":"4,5,11,12,18,19,25,26,30*"},{"month":5,"days":"1,2,3,8*,9,10,11+,16,17,23,24,30,31"},{"month":6,"days":"6,7,11*,12,13,14,20,21,27,28"},{"month":7,"days":"4,5,11,12,18,19,25,26"},{"month":8,"days":"1,2,8,9,15,16,22,23,29,30"},{"month":9,"days":"5,6,12,13,19,20,26,27"},{"month":10,"days":"3,4,10,11,17,18,24,25,31"},{"month":11,"days":"1,3*,4,7,8,14,15,21,22,28,29"},{"month":12,"days":"5,6,12,13,19,20,26,27,31+"}],"transitions":[{"from":"01.03","to":"01.09"},{"from":"03.08","to":"03.09"},{"from":"05.09","to":"05.11"},{"from":"01.04","to":"12.31"}],"statistic":{"workdays":247,"holidays":118,"hours40":1972,"hours36":1774.4,"hours24":1181.6}}';

    $factory->shouldReceive('createRequest')->andReturn(mock(RequestInterface::class));
    $client->shouldReceive('sendRequest')->andReturn($response);
    $response->shouldReceive('getStatusCode')->andReturn(304);
    $response->shouldReceive('getBody')->andReturn($stream);
    $stream->shouldReceive('getContents')->andReturn($json);
    $provider->shouldReceive('getContent')->andReturn($json);

    $holidays = $provider->getConfiguration('ru', 2026);

    expect($holidays)->toBeArray()
        ->and($holidays)->toHaveCount(122)
        ->and($holidays['2026-01-01']->type)->toBe(DayType::Holiday)
        ->and($holidays['2026-01-01']->name)->toBe('')
        ->and($holidays['2026-04-30']->type)->toBe(DayType::PreHoliday);
});
