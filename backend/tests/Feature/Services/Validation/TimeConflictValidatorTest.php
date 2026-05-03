<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\TimeConflictValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('passes when there is no time conflict', function () {
    $user = User::factory()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new TimeConflictValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('16:00', '22:00')
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('throws exception when shifts overlap', function () {
    $user = User::factory()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '10:00',
        'shift_end' => '18:00',
        'position_id' => $position->id,
    ]);

    $validator = new TimeConflictValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('12:00', '14:00')
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});

it('ignores the shift being currently edited', function () {
    $user = User::factory()->create();
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    $shiftToEdit = Shift::create([
        'user_id' => $user->id,
        'date' => '2026-05-10',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'position_id' => $position->id,
    ]);

    $validator = new TimeConflictValidator;

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2026-05-10')
        ->withShiftTime('09:00', '17:00')
        ->withNoLimits()
        ->forUpdate($shiftToEdit->id)
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});
