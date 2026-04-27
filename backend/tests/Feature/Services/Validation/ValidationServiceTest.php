<?php

declare(strict_types=1);

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\User;
use App\Services\Validation\ValidationService;
use Illuminate\Validation\ValidationException;

it('passes validation when all validators pass', function () {
    $user = User::factory()
        ->employee()
        ->withMinuteLimits()
        ->create();
    $position = Position::factory()
        ->create();

    $user->positions()
        ->attach($position->id);

    $data = new ShiftValidationData(
        userId: $user->id,
        positionId: $position->id,
        date: '2026-05-15',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        allowedPositionIds: [$position->id],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: $user->min_break_minutes,
        maxMinutesPerQuarter: $user->max_minutes_per_quarter,
        ignoreShiftId: null,
    );

    $service = app(ValidationService::class);

    expect(fn () => $service->validate($data))
        ->not->toThrow(ValidationException::class);
});

it('throws exception when position permission validator fails', function () {
    $user = User::factory()
        ->employee()
        ->withMinuteLimits()
        ->create();
    $position = Position::factory()
        ->create();

    $data = new ShiftValidationData(
        userId: $user->id,
        positionId: $position->id,
        date: '2026-05-15',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        allowedPositionIds: [],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: $user->min_break_minutes,
        maxMinutesPerQuarter: $user->max_minutes_per_quarter,
        ignoreShiftId: null,
    );

    $service = app(ValidationService::class);

    expect(fn () => $service->validate($data))
        ->toThrow(ValidationException::class);
});
