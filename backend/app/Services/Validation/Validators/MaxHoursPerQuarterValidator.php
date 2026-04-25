<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Services\Validation\Helpers\TimeHelper;

class MaxHoursPerQuarterValidator extends BaseHourValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        if ($shift->maxMinutesPerQuarter === null) {
            return;
        }

        $quarterRange = TimeHelper::createQuarterRange($shift->date);
        $quarterlyMinuteLimit = $shift->maxMinutesPerQuarter;

        $minutesInQuarter = $this->retrieveWorkedMinutesInRange(
            $shift,
            $quarterRange->start,
            $quarterRange->end
        );
        $minutesInQuarter += $shift->accumulatedBatchMinutes;

        $difference = TimeHelper::calculateMinutesDifference(
            $shift->shiftStart,
            $shift->shiftEnd
        );

        $totalMinutes = $minutesInQuarter + $difference;

        if ($totalMinutes > $quarterlyMinuteLimit) {
            $excessMinutes = $totalMinutes - $quarterlyMinuteLimit;

            $this->throwExceededLimitException(
                'quarter',
                $quarterlyMinuteLimit / 60,
                round($excessMinutes / 60, 1)
            );
        }
    }
}
