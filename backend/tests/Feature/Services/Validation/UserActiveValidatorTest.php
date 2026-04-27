<?php

declare(strict_types=1);

use App\DataTransferObjects\ShiftValidationData;
use App\Models\User;
use App\Services\Validation\Validators\UserActiveValidator;
use Illuminate\Validation\ValidationException;

it('should not throw a ValidationException when user is active', function () {

    $validator = new UserActiveValidator;

    $user = User::factory()->employee()->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2025-01-29',
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
    );

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('should throw a ValidationException when user is inactive', function () {

    $validator = new UserActiveValidator;

    $user = User::factory()->employee()->inactive()->create();

    $dto = new ShiftValidationData(
        userId: $user->id,
        date: '2025-01-29',
        shiftStart: '08:00:00',
        shiftEnd: '16:00:00',
        positionId: 1,
        allowedPositionIds: [1, 2, 3],
        maxMinutesPerMonth: null,
        minBreakMinutes: null,
        maxMinutesPerQuarter: null,
    );

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});
