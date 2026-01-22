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
        ->withMinuteLimits()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withMinuteWorked(9300)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);
});

it('passes when total hours are within monthly limit', function () {
    $user = User::factory()
        ->employee()
        ->withMinuteLimits()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withMinuteWorked(6000)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});

it('passes when user has no monthly hour limit', function () {
    $user = User::factory()
        ->employee()
        ->withoutMinuteLimits()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withMinuteWorked(18000)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});

it('ignores current shift when updating', function () {
    $user = User::factory()
        ->employee()
        ->withMinuteLimits()
        ->create();

    $b1 = Position::factory()->create();

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withMinuteWorked(9000)
        ->create();

    $shiftToEdit = Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-15')
        ->withTimes('08:00', '16:00')
        ->withMinuteWorked(480)
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-15',
        shiftStart: '08:00',
        shiftEnd: '18:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: 9600,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: $shiftToEdit->id,
    );

    $validator = new MaxHoursPerMonthValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});

it('throws exception when accumulated batch minutes exceed monthly limit', function () {
    $user = User::factory()
        ->employee()
        ->withMinuteLimits() // limit 160
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withMinuteWorked(9000) // 10 zapasu
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '13:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
        accumulatedBatchMinutes: 360
    );
    $validator = new MaxHoursPerMonthValidator;
    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);

});

it('passes when accumulated batch minutes does not exceed monthly limit', function () {
    $user = User::factory()
        ->employee()
        ->withMinuteLimits() // limit 160
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->withMinuteWorked(9000) // 10 zapasu
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '08:00',
        shiftEnd: '13:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: $user->max_minutes_per_month,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
        accumulatedBatchMinutes: 120
    );
    $validator = new MaxHoursPerMonthValidator;
    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);

});
