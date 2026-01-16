<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;

class PositionUniquenessValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        $conflict = $this->hasConflict($shift);

        if ($conflict) {
            $this->throwConflictException('User has another shift for this position on the selected date');
        }

    }

    private function hasConflict(ShiftValidationData $shift): bool
    {
        return Shift::where('user_id', $shift->userId)
            ->where('date', $shift->date)
            ->where('position_id', $shift->positionId)
            ->excluding($shift->ignoreShiftId)
            ->exists();

    }
}
