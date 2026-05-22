# PHP Production Calendar

Гибкий инструмент для работы с производственным календарем. 
Позволяет рассчитывать рабочие дни, учитывать государственные праздники и переносы, 
используя данные из API или локальных файлов.

## Особенности

- Поддержка PSR-17/18 (любой HTTP-клиент: Guzzle, Symfony и т.д.).
- Поддержка PSR-16 для кеширования данных.
- Гибкая система провайдеров (API, локальный JSON, замыкания).
- Расчет интервалов и добавление рабочих дней.

## Установка
```bash
composer require kosmosafive/production-calendar
```

## Быстрый старт
```php
use MyCalendar\ProductionCalendar;
use MyCalendar\Providers\XmlCalendarProvider;
use MyCalendar\Providers\CachedHolidayProvider;

// 1. Настройка провайдера (с использованием вашего HTTP-клиента)
$apiProvider = new XmlCalendarProvider($httpClient, $requestFactory);

// 2. Добавляем кеширование (любой PSR-16 драйвер)
$provider = new CachedHolidayProvider($apiProvider, $cache);

// 3. Инициализация календаря
$calendar = new ProductionCalendar($provider, 'ru');

// Проверка дня
$calendar->isWorkday(new DateTime('2026-01-01')); // false (Новый год)

// Расчет количества рабочих дней
$workdaysCount = $calendar->countWorkdays(
    new DateTime('2026-05-01'),
    new DateTime('2026-05-31')
);

// Прибавление 10 рабочих дней
$deadline = $calendar->addWorkdays(new DateTime('now'), 10);
```

## Продвинутая конфигурация

### Composite Provider (Приоритеты)

Вы можете использовать CompositeProvider, чтобы сначала проверять локальные исключения, а затем обращаться к API:
```php
use MyCalendar\Providers\CompositeProvider;
use MyCalendar\Providers\JsonFileProvider;

$composite = new CompositeProvider(
    new JsonFileProvider(__DIR__ . '/config/holidays'), // Сначала ищем тут
    new XmlCalendarProvider($client, $factory)          // Если файла нет, идем в API
);
```

### Итерация по рабочим дням

Метод возвращает генератор, что экономит память при больших периодах:
```php
foreach ($calendar->getWorkdaysIterator($start, $end) as $date) {
    echo $date->format('Y-m-d');
}
```
### Структура JSON файла (для локального провайдера)

Файлы должны называться по шаблону {country}_{year}.json (например, ru_2026.json) 
и соответствовать формату xmlcalendar.ru:
```json
{
  "months": [
    {
      "month": 1,
      "days": "1,2,3,4,5,6,7,8,9+,10,11,17,18,24,25,31"
    }
  ],
  "transitions": [
    {"from": "01.03", "to": "01.09"}
  ]
}
```

Выходные дни не обязательно добавлять в конфигурацию.

Специальные символы в конце дня месяца:
- символ "+" (например, 9+) означает перенос рабочего дня
- символ "*" (например, 31\*) означает сокращенный рабочий день