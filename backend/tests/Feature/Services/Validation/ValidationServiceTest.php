<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\User;
use App\Services\Validation\ValidationService;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('passes validation when all validators pass', function () {
    $user = User::factory()->employee()->withMinuteLimits()->create();
    $position = Position::factory()->create();
    $user->positions()->attach($position->id);

    $data = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($position)
        ->onDate('2026-05-15')
        ->withShiftTime('08:00', '16:00')
        ->create();

    $service = app(ValidationService::class);

    expect(fn () => $service->validate($data))->not->toThrow(ValidationException::class);
});

it('throws exception when position permission validator fails', function () {
    $user = User::factory()->employee()->withMinuteLimits()->create();
    $position = Position::factory()->create();

    $data = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($position)
        ->onDate('2026-05-15')
        ->withShiftTime('08:00', '16:00')
        ->create();

    $service = app(ValidationService::class);

    expect(fn () => $service->validate($data))->toThrow(ValidationException::class);
});
