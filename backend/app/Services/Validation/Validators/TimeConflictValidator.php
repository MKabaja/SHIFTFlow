<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;
use Illuminate\Validation\ValidationException;

class TimeConflictValidator
{
    /**
     * @param  ShiftValidationData  $shift  Data Transfer Object
     */
    public function validate(ShiftValidationData $shift): void
    {
        $conflict = $this->findConflict($shift);

        if ($conflict) {
            throw ValidationException::withMessages([
                'shift_start' => ['User has shift during this time'],
            ]);
        }

    }

    private function findConflict(ShiftValidationData $shift): bool
    {
        return Shift::where('user_id', $shift->userId)
            ->where('date', $shift->date)
            ->excluding($shift->ignoreShiftId)
            ->whereOverlapping($shift->shiftStart, $shift->shiftEnd)
            ->exists();
    }
}
