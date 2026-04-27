<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;

class TimeConflictValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        $conflict = $this->hasConflict($shift);

        if ($conflict) {
            $this->throwConflictException('User has another shift during this time');
        }

    }

    private function hasConflict(ShiftValidationData $shift): bool
    {
        return Shift::where('user_id', $shift->userId)
            ->where('date', $shift->date)
            ->excluding($shift->ignoreShiftId)
            ->whereOverlapping($shift->shiftStart, $shift->shiftEnd)
            ->exists();
    }
}
