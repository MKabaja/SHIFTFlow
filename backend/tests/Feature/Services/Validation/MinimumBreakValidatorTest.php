<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\MinimumBreakValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('passes if there is no previous shift in the database', function () {
    $user = User::factory()->employee()->create(['name' => 'Jan Kowalski']);
    $validator = new MinimumBreakValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('16:00', '22:00')
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('finishes validation early if minBreakHours is null', function () {
    $user = User::factory()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new MinimumBreakValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('16:00', '22:00')
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('ignores the current shift when validating during an update', function () {
    $user = User::factory()->employee()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    $existingShift = Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new MinimumBreakValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('09:00', '17:00')
        ->forUpdate($existingShift->id)
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('fails when break is too short on the same day', function () {
    $user = User::factory()->employee()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '12:00',
        'position_id' => $position->id,
    ]);

    $validator = new MinimumBreakValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('14:00', '17:00')
        ->create();

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});

it('fails when break is too short between consecutive days', function () {
    $user = User::factory()->employee()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '14:00',
        'shift_end' => '22:00',
        'position_id' => $position->id,
    ]);

    $validator = new MinimumBreakValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-11')
        ->withShiftTime('06:00', '14:00')
        ->create();

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});
