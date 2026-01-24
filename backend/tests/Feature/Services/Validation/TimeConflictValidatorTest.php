<?php

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\TimeConflictValidator;
use Illuminate\Validation\ValidationException;

it('passes when there is no time conflict', function () {

    $user = User::factory()->create();

    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);

    $user->positions()->attach($position->id);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new TimeConflictValidator;

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '16:00',
        shiftEnd: '22:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
    );
    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);

});

it('throws exception when shifts overlap', function () {

    $user = User::factory()->create();
    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);

    $user->positions()->attach($position->id);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '10:00',
        'shift_end' => '18:00',
        'position_id' => $position->id,
    ]);

    $validator = new TimeConflictValidator;

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '12:00',
        shiftEnd: '14:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
    );

    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);

});

it('ignores the shift being currently edited', function () {

    $user = User::factory()->create();
    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);

    $user->positions()->attach($position->id);

    $shiftToEdit = Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new TimeConflictValidator;

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '09:00',
        shiftEnd: '17:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
        ignoreShiftId: $shiftToEdit->id
    );

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);

});
