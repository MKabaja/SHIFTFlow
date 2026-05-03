<?php

declare(strict_types=1);

use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Services\Validation\Validators\MaxHoursPerQuarterValidator;
use Illuminate\Validation\ValidationException;
use Tests\Factories\ShiftValidationDataFactory;

it('throws exception when user has exceeded quarterly hourly limit', function () {
    $user = User::factory()->employee()->withMinuteLimits()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->withMinuteWorked(28500)->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-05-11')
        ->withShiftTime('08:00', '16:00')
        ->create();

    $validator = new MaxHoursPerQuarterValidator;

    expect(fn () => $validator->validate($dto))->toThrow(ValidationException::class);
});

it('passes when total hours are within quarter limit', function () {
    $user = User::factory()->employee()->withMinuteLimits()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->withMinuteWorked(24000)->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-05-11')
        ->withShiftTime('08:00', '16:00')
        ->create();

    $validator = new MaxHoursPerQuarterValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('passes when user has no quarter hour limit', function () {
    $user = User::factory()->employee()->withoutMinuteLimits()->create();
    $b1 = Position::factory()->create(['name' => 'B1']);

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withTimes('08:00', '16:00')->withMinuteWorked(30000)->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-05-11')
        ->withShiftTime('08:00', '16:00')
        ->create();

    $validator = new MaxHoursPerQuarterValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});

it('ignores current shift when updating', function () {
    $user = User::factory()->employee()->withMinuteLimits()->create();
    $b1 = Position::factory()->create();

    Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-10')
        ->withMinuteWorked(28200)->create();

    $shiftToEdit = Shift::factory()->forUser($user)->forPosition($b1)->onDate('2026-05-15')
        ->withTimes('08:00', '16:00')->withMinuteWorked(480)->create();

    $dto = ShiftValidationDataFactory::new()
        ->withUser($user)
        ->withPosition($b1)
        ->onDate('2026-05-15')
        ->withShiftTime('08:00', '18:00')
        ->forUpdate($shiftToEdit->id)
        ->create();

    $validator = new MaxHoursPerQuarterValidator;

    expect(fn () => $validator->validate($dto))->not->toThrow(ValidationException::class);
});
