<?php

use App\Services\Validation\Helpers\TimeHelper;

it('formats date for query', function ($input, $expected) {

    $result = TimeHelper::formatDateForQuery($input);

    expect($result)->toBe($expected);
})->with([
    'ISO format' => ['2026-01-10', '2026-01-10'],
    'SQL datetime' => ['2026-01-10 15:30:00', '2026-01-10'],
]);

it('throws exception for invalid date format', function () {
    TimeHelper::formatDateForQuery('to-nie-jest-data');
})->throws(\InvalidArgumentException::class, 'Unable to parse date format: to-nie-jest-data');

it('formats time for query', function ($input, $expected) {
    $result = TimeHelper::formatTimeForQuery($input);

    expect($result)->toBe($expected);
})->with([
    'Standard format' => ['15:30', '15:30'],
    'With seconds' => ['15:30:00', '15:30'],
]);

it('throws exception for invalid time format', function () {
    TimeHelper::formatTimeForQuery('invalid-time');
})->throws(\InvalidArgumentException::class);
