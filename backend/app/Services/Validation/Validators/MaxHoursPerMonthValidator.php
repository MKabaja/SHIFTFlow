<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Services\Validation\Helpers\TimeHelper;

class MaxHoursPerMonthValidator extends BaseHourValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        if ($shift->maxMinutesPerMonth === null) {
            return;
        }

        $monthRange = TimeHelper::createMonthRange($shift->date);
        $monthlyMinuteLimit = $shift->maxMinutesPerMonth;

        $minutesInMonth = $this->retrieveWorkedMinutesInRange(
            $shift,
            $monthRange->start,
            $monthRange->end
        );
        $minutesInMonth += $shift->accumulatedBatchMinutes;

        $difference = TimeHelper::calculateMinutesDifference(
            $shift->shiftStart,
            $shift->shiftEnd
        );

        $totalMinutes = $minutesInMonth + $difference;

        if ($totalMinutes > $monthlyMinuteLimit) {

            $excessMinutes = $totalMinutes - $monthlyMinuteLimit;

            $this->throwExceededLimitException(
                'month',
                intdiv($monthlyMinuteLimit, 60),
                round($excessMinutes / 60, 1)
            );
        }
    }
}
