<?php

namespace App\Services\Validation;

use App\DataTransferObjects\ShiftValidationData;
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

    /**
     * Validate shift data through all validators.
     * Executes validators in optimized order (fast to slow).
     * Stops on first validation failure.
     *
     * @param ShiftValidationData Data Transfer Object
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(ShiftValidationData $shift): void
    {

        $validators = [
            $this->positionPermissionValidator,
            $this->availabilityValidator,
            $this->positionUniquenessValidator,
            $this->timeConflictValidator,
            $this->minimumBreakValidator,
            $this->maxHoursPerMonthValidator,
            $this->maxHoursPerQuarterValidator,
        ];

        foreach ($validators as $validator) {
            $validator->validate($shift);
        }

    }
}
