<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Services\Validation\Helpers\TimeHelper;

class MaxHoursPerMonthValidator extends BaseHourValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        if ($shift->maxHoursPerMonth === null) {
            return;
        }

        $monthRange = TimeHelper::createMonthRange($shift->date);
        $monthlyMinuteLimit = $shift->maxHoursPerMonth * 60;

        $minutesInMonth = $this->retrieveWorkedMinutesInRange(
            $shift,
            $monthRange->start,
            $monthRange->end
        );

        $difference = TimeHelper::calculateMinutesDifference(
            $shift->shiftStart,
            $shift->shiftEnd
        );

        $totalMinutes = $minutesInMonth + $difference;

        if ($totalMinutes > $monthlyMinuteLimit) {
            $totalHours = $totalMinutes / 60;
            $excessHours = $totalHours - $shift->maxHoursPerMonth;

            $this->throwExceededLimitException(
                'month',
                $shift->maxHoursPerMonth,
                $excessHours
            );
        }
    }
}
