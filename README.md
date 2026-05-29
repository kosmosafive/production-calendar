# PHP Production Calendar

Гибкий инструмент для работы с производственным календарем. 
Позволяет рассчитывать рабочие дни, учитывать государственные праздники и переносы, 
используя данные из API или локальных файлов.

## Особенности

- Поддержка PSR-17/18 (любой HTTP-клиент: Guzzle, Symfony и т.д.).
- Поддержка PSR-16 для кеширования данных.
- Гибкая система провайдеров (API, локальный JSON, замыкания).
- Расчет интервалов и добавление рабочих дней.
- Итераторы для эффективной обработки периодов без загрузки всех данных в память.
- Поддержка нескольких стран через ISO-код (например, `ru`, `us`).

## Установка
```bash
composer require kosmosafive/production-calendar
```

## Быстрый старт
```php
use Kosmosafive\ProductionCalendar\ProductionCalendar;
use Kosmosafive\ProductionCalendar\Provider\XmlCalendarProvider;
use Kosmosafive\ProductionCalendar\Provider\CachedProvider;

// 1. Настройка провайдера (с использованием вашего HTTP-клиента)
$apiProvider = new XmlCalendarProvider($httpClient, $requestFactory);

// 2. Добавляем кеширование (любой PSR-16 драйвер)
$provider = new CachedProvider($apiProvider, $cache);

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

## API календаря

### Основные методы

#### `isWorkday(DateTimeInterface $date): bool`
Проверяет, является ли дата рабочим днем. Учитывает праздники, переносы и выходные.

#### `countWorkdays(DateTimeInterface $start, DateTimeInterface $end): int`
Подсчитывает количество рабочих дней в указанном диапазоне (включая границы).

#### `addWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable`
Прибавляет указанное количество рабочих дней к дате. Отрицательное значение позволяет вычитать дни.

#### `subtractWorkdays(DateTimeInterface $date, int $days): DateTimeImmutable`
Вычитает указанное количество рабочих дней из даты (алиас для `addWorkdays` с отрицательным значением).

#### `getClosestWorkday(DateTimeInterface $date, bool $forward = true): DateTimeImmutable`
Находит ближайший рабочий день. Параметр `$forward` определяет направление поиска: вперед (`true`) или назад (`false`).

### Итераторы

#### `getWorkdaysIterator(DateTimeInterface $start, DateTimeInterface $end): Generator<DateTimeImmutable>`
Возвращает генератор рабочих дней в диапазоне. Экономит память при обработке больших периодов.

#### `getFullCalendarIterator(DateTimeInterface $start, DateTimeInterface $end): Generator<CalendarDay>`
Возвращает полную информацию о каждом дне периода, включая тип дня, название праздника и информацию о переносах.

#### `hasHolidays(DateTimeInterface $start, DateTimeInterface $end): bool`
Проверяет наличие праздничных дней в указанном диапазоне.

## Продвинутая конфигурация

### Composite Provider (Приоритеты)

Вы можете использовать CompositeProvider, чтобы сначала проверять локальные исключения, а затем обращаться к API:
```php
use Kosmosafive\ProductionCalendar\Provider\CompositeProvider;
use Kosmosafive\ProductionCalendar\Provider\JsonProvider;

$composite = new CompositeProvider(
    new JsonProvider(__DIR__ . '/config/holidays'), // Сначала ищем тут
    new XmlCalendarProvider($client, $factory)          // Если файла нет, идем в API
);
```

### Closure Provider (Кастомная логика)

Для особых случаев можно использовать ClosureProvider:
```php
use Kosmosafive\ProductionCalendar\Provider\ClosureProvider;

$closureProvider = new ClosureProvider(function (string $country, int $year) {
    // Ваша логика получения данных
    return [
        '2026-01-01' => new Day(new DateTimeImmutable('2026-01-01'), Type::Holiday, 'New Year'),
    ];
});
```

### Структура JSON файла (для локального провайдера)

Файлы должны называться по шаблону `{country}_{year}.json` (например, `ru_2026.json`) 
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
- символ `+` (например, `9+`) означает перенос рабочего дня
- символ `*` (например, `31*`) означает сокращенный рабочий день

## Типы дней

Библиотека использует следующие типы дней:

| Тип | Описание |
|-----|----------|
| `Type::Working` | Обычный рабочий день |
| `Type::Weekend` | Выходной день (суббота/воскресенье) |
| `Type::Holiday` | Праздничный день |
| `Type::PreHoliday` | Предпраздничный (сокращенный) день |
| `Type::Transferred` | Перенесенный рабочий день |

## Примеры использования

### Расчет срока выполнения задачи
```php
$startDate = new DateTime('2026-01-15');
$workDaysNeeded = 20;
$deadline = $calendar->addWorkdays($startDate, $workDaysNeeded);
echo "Дедлайн: " . $deadline->format('Y-m-d');
```

### Поиск последнего рабочего дня месяца
```php
$lastDayOfMonth = new DateTime('2026-01-31');
$lastWorkday = $calendar->getClosestWorkday($lastDayOfMonth, forward: false);
echo "Последний рабочий день: " . $lastWorkday->format('Y-m-d');
```

### Анализ квартала
```php
$start = new DateTime('2026-01-01');
$end = new DateTime('2026-03-31');

$workDaysCount = $calendar->countWorkdays($start, $end);
$hasHolidays = $calendar->hasHolidays($start, $end);

echo "Рабочих дней в Q1: $workDaysCount\n";
echo "Есть праздники: " . ($hasHolidays ? 'да' : 'нет') . "\n";
```

## Требования

- PHP 8.4+
- PSR-17 (HTTP Factory) — для XML API провайдера
- PSR-18 (HTTP Client) — для XML API провайдера
- PSR-16 (Cache) — опционально, для кеширования

## Лицензия

MIT License. См. файл [LICENSE](LICENSE) для подробностей.
