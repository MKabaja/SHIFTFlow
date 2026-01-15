<?php

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\MaxHoursPerMonthValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('throws exception when user has exceeded hourly limit', function () {
    $user = User::factory()
        ->employee()
        ->withHourLimits()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withHoursWorked(155 * 60)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxHoursPerMonth: $user->max_hours_per_month,
        minBreakHours: null,
        maxHoursPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);
});

it('passes when total hours are within monthly limit', function () {
    $user = User::factory()
        ->employee()
        ->withHourLimits()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withHoursWorked(100 * 60)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxHoursPerMonth: $user->max_hours_per_month,
        minBreakHours: null,
        maxHoursPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});

it('passes when user has no monthly hour limit', function () {
    $user = User::factory()
        ->employee()
        ->withoutHourLimits()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withHoursWorked(300 * 60)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxHoursPerMonth: $user->max_hours_per_month,
        minBreakHours: null,
        maxHoursPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});

it('ignores current shift when updating', function () {
    $user = User::factory()
        ->employee()
        ->withHourLimits()
        ->create();

    $b1 = Position::factory()->create();

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withHoursWorked(150 * 60)
        ->create();

    $shiftToEdit = Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-15')
        ->withTimes('08:00', '16:00')
        ->withHoursWorked(8 * 60)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-15',
        shiftStart: '08:00',
        shiftEnd: '18:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxHoursPerMonth: 160,
        minBreakHours: null,
        maxHoursPerQuarter: null,
        ignoreShiftId: $shiftToEdit->id,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});
