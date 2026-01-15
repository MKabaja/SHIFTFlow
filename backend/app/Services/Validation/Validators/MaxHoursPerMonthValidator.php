<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;
use App\Services\Validation\Helpers\TimeHelper;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class MaxHoursPerMonthValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        if ($shift->maxHoursPerMonth === null) {
            return;
        }

        $monthRange = TimeHelper::createMonthRange($shift->date);

        $monthlyMinuteLimit = $shift->maxHoursPerMonth * 60;

        $minutesInMonth = $this->retrieveMonthlyWorkedMinutes(
            $shift,
            $monthRange->start,
            $monthRange->end
        );

        $shiftStart = TimeHelper::createFullDateTime(
            $shift->date,
            $shift->shiftStart
        );
        $shiftEnd = TimeHelper::createFullDateTime(
            $shift->date,
            $shift->shiftEnd
        );

        if ($shiftEnd < $shiftStart) {
            $shiftEnd->addDay();
        }

        $difference = $shiftStart->diffInMinutes($shiftEnd);
        $totalMinutes = $minutesInMonth + $difference;
        $totalHours = $totalMinutes / 60;

        if ($totalMinutes > $monthlyMinuteLimit) {
            throw ValidationException::withMessages([
                'max_hours' => [
                    sprintf(
                        'Max hours exceeded: %.2fh > %dh',
                        $totalHours,
                        $shift->maxHoursPerMonth
                    ),
                ],
            ]);
        }

    }

    /**
     * This funciton uses custom builder excluding defined at:
     * \App\Models\Shift
     */
    private function retrieveMonthlyWorkedMinutes(ShiftValidationData $shift,
        Carbon $start,
        Carbon $end
    ): int {
        return Shift::where('user_id', $shift->userId)
            ->whereBetween('date', [$start, $end])
            ->excluding($shift->ignoreShiftId)
            ->sum('hours_worked') ?? 0;

    }
}
