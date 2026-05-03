<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Availability;

class AvailabilityValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        $availabilityConflict = $this->hasConflict($shift);

        if ($availabilityConflict
            && ! $availabilityConflict->is_available
        ) {
            $this->throwConflictException('User has no availability on the selected date');
        }

    }

    private function hasConflict(ShiftValidationData $shift): ?Availability
    {
        return Availability::where('user_id', $shift->userId)
            ->where('date', $shift->date)
            ->first();
    }
}
