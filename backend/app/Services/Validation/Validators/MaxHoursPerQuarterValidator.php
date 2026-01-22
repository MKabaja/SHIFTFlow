<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Services\Validation\Helpers\TimeHelper;

class MaxHoursPerQuarterValidator extends BaseHourValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        if ($shift->maxHoursPerQuarter === null) {
            return;
        }

        $quarterRange = TimeHelper::createQuarterRange($shift->date);
        $quarterlyMinuteLimit = $shift->maxHoursPerQuarter * 60;

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
            $totalHours = $totalMinutes / 60;
            $excessHours = $totalHours - $shift->maxHoursPerQuarter;

            $this->throwExceededLimitException(
                'quarter',
                $shift->maxHoursPerQuarter,
                $excessHours
            );
        }
    }
}
