<?php

use App\Services\Validation\Helpers\TimeHelper;
use Carbon\Carbon;

uses(Tests\TestCase::class);

it('formats date for query', function ($input, $expected) {

    $result = TimeHelper::formatDateForQuery($input);

    expect($result)->toBe($expected);
})->with([
    'ISO format' => ['2026-01-10', '2026-01-10'],
    'SQL datetime' => ['2026-01-10 15:30:00', '2026-01-10'],
]);

test('it throws exception for invalid date format', function () {
    expect(fn () => TimeHelper::formatDateForQuery('to-nie-jest-data'))
        ->toThrow(\InvalidArgumentException::class, 'Unable to parse date format: to-nie-jest-data');
});

it('formats time for query', function ($input, $expected) {
    $result = TimeHelper::formatTimeForQuery($input);

    expect($result)->toBe($expected);
})->with([
    'Standard format' => ['15:30', '15:30'],
    'With seconds' => ['15:30:00', '15:30'],
]);

test('it throws exception for invalid time format', function () {
    expect(fn () => TimeHelper::formatTimeForQuery('invalid-time'))
        ->toThrow(\InvalidArgumentException::class, 'Unable to parse time format: invalid-time');
});
test('it creates a correct month range from a string date', function () {

    $range = TimeHelper::createMonthRange('2024-03-15');

    expect($range->start->toDateString())->toBe('2024-03-01')
        ->and($range->end->toDateString())->toBe('2024-03-31');

    expect($range->start)->toBeInstanceOf(Carbon::class);
});

test('it works correctly for leap years (February)', function () {

    $range = TimeHelper::createMonthRange('2024-02-10');

    expect($range->end->toDateString())->toBe('2024-02-29');
});

test('it handles DateTimeInterface objects as input', function () {
    $date = new DateTime('2024-12-25');

    $range = TimeHelper::createMonthRange($date);

    expect($range->start->toDateString())->toBe('2024-12-01')
        ->and($range->end->toDateString())->toBe('2024-12-31');
});

test('it creates correct quarter range for Q1 (January to March)', function () {

    $range = TimeHelper::createQuarterRange('2024-02-15');

    expect($range->start->toDateString())->toBe('2024-01-01')
        ->and($range->end->toDateString())->toBe('2024-03-31');
});

test('it creates correct quarter range for Q4 (October to December)', function () {

    $range = TimeHelper::createQuarterRange('2024-12-31');

    expect($range->start->toDateString())->toBe('2024-10-01')
        ->and($range->end->toDateString())->toBe('2024-12-31');
});

test('it handles different quarters correctly throughout the year', function ($input, $expectedStart, $expectedEnd) {
    $range = TimeHelper::createQuarterRange($input);

    expect($range->start->toDateString())->toBe($expectedStart)
        ->and($range->end->toDateString())->toBe($expectedEnd);
})->with([
    'Q1 edge' => ['2024-03-31', '2024-01-01', '2024-03-31'],
    'Q2 start' => ['2024-04-01', '2024-04-01', '2024-06-30'],
    'Q3 middle' => ['2024-08-15', '2024-07-01', '2024-09-30'],
]);

test('it returns objects of type Carbon', function () {
    $range = TimeHelper::createQuarterRange('2024-01-01');

    expect($range->start)->toBeInstanceOf(Carbon::class)
        ->and($range->end)->toBeInstanceOf(Carbon::class);
});

test('it calculates difference between times within the same day', function () {

    $result = TimeHelper::calculateMinutesDifference('08:00', '10:30');

    expect($result)->toBe(150);
});

test('it handles crossing midnight correctly', function () {

    $result = TimeHelper::calculateMinutesDifference('22:00', '02:00');

    expect($result)->toBe(240);
});

test('it returns zero when times are identical', function () {
    $result = TimeHelper::calculateMinutesDifference('12:00', '12:00');

    expect($result)->toBe(0);
});

test('it calculates difference for nearly 24 hours', function () {

    $result = TimeHelper::calculateMinutesDifference('08:00', '07:59');

    expect($result)->toBe(1439);
});

test('it works with full datetime strings', function () {
    $result = TimeHelper::calculateMinutesDifference('2024-01-01 10:00:00', '2024-01-01 11:00:00');

    expect($result)->toBe(60);
});
