<?php

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\User;
use App\Services\Validation\Validators\PositionPermissionValidator;
use Illuminate\Validation\ValidationException;

it('passes when user has position permission', function () {

    $validator = new PositionPermissionValidator;

    $user = User::factory()->create(['role' => 'employee']);

    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);

    $user->positions()->attach($position->id);

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2025-01-29',
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: $position->id,
        allowedPositionIds: $user->positions->pluck('id')->toArray(),
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
    );

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('throws exception when user lacks position permission', function () {

    $validator = new PositionPermissionValidator;
    $user = User::factory()->create(['role' => 'employee']);
    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);
    $forbiddenPosition = Position::create(['name' => 'WS',  'description' => 'Staszic Shaft Lift Operator']);
    $user->positions()->attach($position->id);

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2025-01-29',
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: $forbiddenPosition->id,
        allowedPositionIds: $user->positions->pluck('id')->toArray(),
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
    );

    $validator->validate($dto);
})->throws(ValidationException::class);
