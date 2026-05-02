<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\PositionUniquenessValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('throws exception when user has duplicate position on same day', function () {
    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);
    $user->positions()->attach($b1->id);

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-05-10')
        ->withShiftTime('16:00', '22:00')
        ->withNoLimits()
        ->create();

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});

it('passes when user has different position on same day', function () {
    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);
    $k1 = Position::factory()->create(['name' => 'K1']);

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($k1)
        ->onDate('2026-05-10')
        ->withShiftTime('16:00', '22:00')
        ->withNoLimits()
        ->create();

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('passes when shift is on different day with same position', function () {
    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-06-11')
        ->withShiftTime('08:00', '16:00')
        ->withNoLimits()
        ->create();

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('ignores the shift being currently edited', function () {
    $user = User::factory()->employee()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);
    $user->positions()->attach($b1->id);

    $shiftToEdit = Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-05-10')
        ->withShiftTime('09:00', '17:00')
        ->withNoLimits()
        ->forUpdate($shiftToEdit->id)
        ->create();

    $validator = new PositionUniquenessValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});
