<?php

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\User;
use App\Services\Validation\ValidationService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('passes validation when all validators pass', function () {
    $user = User::factory()
        ->employee()
        ->withHourLimits()
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
        maxHoursPerMonth: $user->max_hours_per_month,
        minBreakHours: $user->min_break_hours,
        maxHoursPerQuarter: $user->max_hours_per_quarter,
        ignoreShiftId: null,
    );

    $service = app(ValidationService::class);

    expect(fn () => $service->validate($data))
        ->not->toThrow(ValidationException::class);
});

it('throws exception when position permission validator fails', function () {
    $user = User::factory()
        ->employee()
        ->withHourLimits()
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
        maxHoursPerMonth: $user->max_hours_per_month,
        minBreakHours: $user->min_break_hours,
        maxHoursPerQuarter: $user->max_hours_per_quarter,
        ignoreShiftId: null,
    );

    $service = app(ValidationService::class);

    expect(fn () => $service->validate($data))
        ->toThrow(ValidationException::class);
});
