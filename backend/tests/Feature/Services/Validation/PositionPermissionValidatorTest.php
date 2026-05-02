<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\User;
use App\Services\Validation\Validators\PositionPermissionValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('passes when user has position permission', function () {
    $validator = new PositionPermissionValidator;
    $user = User::factory()->create(['role' => 'employee']);
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $user->positions()->attach($position->id);

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2025-01-29')
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('throws exception when user lacks position permission', function () {
    $validator = new PositionPermissionValidator;
    $user = User::factory()->create(['role' => 'employee']);
    $position = Position::create(['name' => 'B1', 'description' => 'Bileter']);
    $forbiddenPosition = Position::create(['name' => 'WS', 'description' => 'Staszic Shaft Lift Operator']);
    $user->positions()->attach($position->id);

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate('2025-01-29')
        ->withPosition($forbiddenPosition)
        ->withNoLimits()
        ->create();

    $validator->validate($dto);
})->throws(ValidationException::class);
