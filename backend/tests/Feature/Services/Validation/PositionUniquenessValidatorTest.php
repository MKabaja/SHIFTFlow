<?php

declare(strict_types=1);

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\PositionUniquenessValidator;
use Illuminate\Validation\ValidationException;

it('throws exception when user has duplicate position on same day', function () {
    $user = User::factory()
        ->employee()
        ->create();

    $b1 = Position::factory()
        ->create(['name' => 'B1']);

    $user->positions()
        ->attach($b1->id);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '16:00',
        shiftEnd: '22:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);
});

it('passes when user has different position on same day', function () {
    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);
    $k1 = Position::factory()->create(['name' => 'K1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '16:00',
        shiftEnd: '22:00',
        positionId: $k1->id,
        allowedPositionIds: [$b1->id, $k1->id],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);

});

it('passes when shift is on different day with same position', function () {
    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);

    Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-06-11',
        shiftStart: '08:00',
        shiftEnd: '16:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: null,
    );

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);

});

it('ignores the shift being currently edited', function () {

    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);

    $user->positions()->attach($b1->id);

    $shiftToEdit = Shift::factory()
        ->forUser($user)
        ->forPosition($b1)
        ->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')
        ->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '09:00',
        shiftEnd: '17:00',
        positionId: $b1->id,
        allowedPositionIds: [$b1->id],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: $shiftToEdit->id
    );

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);

});
