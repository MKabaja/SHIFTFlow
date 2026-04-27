<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;
use App\Services\Validation\Helpers\TimeHelper;

class MinimumBreakValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        $lastShift = $this->findLastShift($shift);

        if (! $lastShift || $shift->minBreakMinutes === null) {
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

        $breakMinutes = $previousShiftEnd->diffInMinutes($currentShiftStart, false);

        if ($breakMinutes < 0) {
            return;
        }

        if ($breakMinutes < $shift->minBreakMinutes) {
            $required = number_format($shift->minBreakMinutes / 60, 1);
            $actual = number_format($breakMinutes / 60, 1);

            $this->throwConflictException("User has insufficient break:{$actual}h, required: {$required}h");

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
