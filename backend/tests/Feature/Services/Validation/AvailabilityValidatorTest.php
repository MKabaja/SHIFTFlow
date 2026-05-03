<?php

declare(strict_types=1);

use App\Models\Availability;
use App\Models\User;
use App\Services\Validation\Validators\AvailabilityValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('throws exception when  user is unavailable', function () {
    $date = '2025-01-29';
    $user = User::factory()->create(['role' => 'employee']);

    Availability::create([
        'user_id' => $user->id,
        'date' => $date,
        'is_available' => false,
    ]);

    $validator = new AvailabilityValidator;
    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate($date)
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});

it('passes when no availability record exists for this date', function () {
    $date = '2025-01-29';
    $user = User::factory()->create(['role' => 'employee']);

    $validator = new AvailabilityValidator;
    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate($date)
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('passes when user is explicitly available', function () {
    $date = '2025-01-29';
    $user = User::factory()->create();

    Availability::create([
        'user_id' => $user->id,
        'date' => $date,
        'is_available' => true,
    ]);

    $validator = new AvailabilityValidator;
    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->onDate($date)
        ->withNoLimits()
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});
