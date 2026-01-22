<?php

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\MinimumBreakValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('passes if there is no previous shift in the database', function () {

    $user = User::factory()->create([
        'role' => 'employee',
        'name' => 'Jan Kowalski',
    ]);

    $validator = new MinimumBreakValidator;

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '16:00',
        shiftEnd: '22:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxMinutesPerMonth: null,
        minBreakMinutes: 660,
        maxMinutesPerQuarter: null,
    );
    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);

});

it('finishes validation early if minBreakHours is null', function () {
    $user = User::factory()->create();
    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new MinimumBreakValidator;

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

it('ignores the current shift when validating during an update', function () {
    $user = User::factory()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    $existingShift = Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '09:00',
        shiftEnd: '17:00',
        positionId: $position->id,
        allowedPositionIds: [$position->id],
        maxMinutesPerMonth: null,
        maxMinutesPerQuarter: null,
        minBreakMinutes: 660,
        ignoreShiftId: $existingShift->id,
    );

    $validator = new MinimumBreakValidator;

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});

it('fails when break is too short on the same day', function () {
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
        'shift_end' => '12:00',
        'position_id' => $position->id,
    ]);

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-10',
        shiftStart: '14:00',
        shiftEnd: '17:00',
        positionId: $position->id,
        allowedPositionIds: [$position->id],
        maxMinutesPerMonth: null,
        maxMinutesPerQuarter: null,
        minBreakMinutes: 660,
    );

    $validator = new MinimumBreakValidator;

    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);

});
it('fails when break is too short between consecutive days', function () {
    $user = User::factory()->create();
    $position = Position::create([
        'name' => 'B1',
        'description' => 'Bileter',
    ]);
    $user->positions()->attach($position->id);
    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '14:00',
        'shift_end' => '22:00',
        'position_id' => $position->id,
    ]);

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2026-05-11',
        shiftStart: '06:00',
        shiftEnd: '14:00',
        positionId: $position->id,
        allowedPositionIds: [$position->id],
        maxMinutesPerMonth: null,
        maxMinutesPerQuarter: null,
        minBreakMinutes: 660,
    );

    $validator = new MinimumBreakValidator;

    expect(fn () => $validator->validate($dto))
        ->toThrow(ValidationException::class);
});
