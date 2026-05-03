<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Validation\Validators\UserActiveValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('should not throw a ValidationException when user is active', function () {
    $validator = new UserActiveValidator;
    $user = User::factory()->employee()->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->create();

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('should throw a ValidationException when user is inactive', function () {
    $validator = new UserActiveValidator;
    $user = User::factory()->employee()->inactive()->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->create();

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});
