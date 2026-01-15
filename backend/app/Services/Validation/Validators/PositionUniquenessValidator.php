<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;
use Illuminate\Validation\ValidationException;

class PositionUniquenessValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        $conflict = $this->findConflict($shift);

        if ($conflict) {
            throw ValidationException::withMessages([
                'position_id' => ["User already has a shift for this position on the selected date:{$shift->date}"],
            ]);
        }

    }

    private function findConflict(ShiftValidationData $shift): bool
    {
        return Shift::where('user_id', $shift->userId)
            ->where('date', $shift->date)
            ->where('position_id', $shift->positionId)
            ->excluding($shift->ignoreShiftId)
            ->exists();

    }
}
