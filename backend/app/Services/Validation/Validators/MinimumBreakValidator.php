<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;
use App\Services\Validation\Helpers\TimeHelper;

class MinimumBreakValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        $lastShift = $this->findLastShift($shift);

        if (! $lastShift || $shift->minBreakHours === null) {
            return;
        }

        $previousShiftEnd = TimeHelper::createFullDateTime(
            $lastShift->date,
            $lastShift->shift_end
        );

        $currentShiftStart = TimeHelper::createFullDateTime(
            $shift->date,
            $shift->shiftStart
        );

        if ($lastShift->shift_end < $lastShift->shift_start) {
            $previousShiftEnd->addDay();
        }

        $breakHours = $previousShiftEnd->diffInMinutes($currentShiftStart, false) / 60;

        if ($breakHours < 0) {
            return;
        }

        if ($breakHours < $shift->minBreakHours) {
            $required = number_format($shift->minBreakHours, 1);
            $actual = number_format($breakHours, 1);

            $this->throwConflictException("User has insufficient break:{$actual}, required: {$required}");

        }

    }

    private function findLastShift(ShiftValidationData $shift): ?Shift
    {
        return Shift::where('user_id', $shift->userId)
            ->excluding($shift->ignoreShiftId)
            ->finishedBefore($shift->date, $shift->shiftStart)
            ->orderByDesc('date')
            ->orderByDesc('shift_end')
            ->first();

    }
}
