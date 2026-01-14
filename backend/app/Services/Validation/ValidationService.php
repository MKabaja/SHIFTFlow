<?php

namespace App\Services\Validation;

use App\Models\User;
use App\Services\Validation\Validators\AvailabilityValidator;
use App\Services\Validation\Validators\MaxHoursPerMonthValidator;
use App\Services\Validation\Validators\MaxHoursPerQuarterValidator;
use App\Services\Validation\Validators\MinimumBreakValidator;
use App\Services\Validation\Validators\PositionPermissionValidator;
use App\Services\Validation\Validators\PositionUniquenessValidator;
use App\Services\Validation\Validators\TimeConflictValidator;

class ValidationService
{
    public function __construct(
        private PositionPermissionValidator $positionPermissionValidator,
        private AvailabilityValidator $availabilityValidator,
        private TimeConflictValidator $timeConflictValidator,
        private MinimumBreakValidator $minimumBreakValidator,
        private MaxHoursPerMonthValidator $maxHoursPerMonthValidator,
        private MaxHoursPerQuarterValidator $maxHoursPerQuarterValidator,
        private PositionUniquenessValidator $positionUniquenessValidator,
    ) {}

    public function validate(
        User $user,
        int $positionId,
        string $date,
        string $shiftStart,
        string $shiftEnd,
        ?int $ignoreShiftId = null
    ): array {
        $errors = [];
        $warnings = [];

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
