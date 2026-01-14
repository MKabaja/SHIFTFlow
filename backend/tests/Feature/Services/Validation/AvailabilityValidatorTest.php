<?php

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Availability;
use App\Models\User;
use App\Services\Validation\Validators\AvailabilityValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('throws exception when  user is unavailable', function () {
    $date = '2025-01-29';

    $user = User::factory()->create(['role' => 'employee']);

    Availability::create([
        'user_id' => $user->id,
        'date' => $date,
        'is_available' => false,

    ]);
    $validator = new AvailabilityValidator;

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: $date,
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxHoursPerMonth: null,
        minBreakHours: null,
        maxHoursPerQuarter: null,
    );

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});

it('passes when no availability record exists for this date', function () {
    $date = '2025-01-29';

    $user = User::factory()->create(['role' => 'employee']);

    $validator = new AvailabilityValidator;

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: $date,
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxHoursPerMonth: null,
        minBreakHours: null,
        maxHoursPerQuarter: null,
    );

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
    $dto = new ShiftValidationData(
        userId: $user->id,
        date: $date,
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxHoursPerMonth: null,
        minBreakHours: null,
        maxHoursPerQuarter: null,
    );

    expect(fn () => $validator->validate($dto))
        ->not->toThrow(ValidationException::class);
});
