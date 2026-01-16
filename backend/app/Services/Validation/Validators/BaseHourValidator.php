<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Shift;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

abstract class BaseHourValidator implements ShiftValidatorInterface
{
    /**
     * Retrieve total worked minutes for user in date range.
     * Optionally ignores specific shift (for updates via ignoreShiftId).
     *
     * @return int Total minutes worked in range
     */
    protected function retrieveWorkedMinutesInRange(
        ShiftValidationData $shift,
        CarbonInterface $startDate,
        CarbonInterface $endDate
    ): int {
        return Shift::where('user_id', $shift->userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->excluding($shift->ignoreShiftId)
            ->sum('hours_worked') ?? 0;
    }

    /**
     * @param  string  $periodTime  validation key (e.g. Month/Quarter)
     * @param  int  $limit  Hour limit that was exceeded
     * @param  float  $excessHours  The difference between the hour limit and the total hours.
     *
     * @throws ValidationException
     */
    protected function throwExceededLimitException(
        string $periodTime,
        int $limit,
        float $excessHours
    ): void {
        $excess = round($excessHours, 1);

        throw ValidationException::withMessages([
            'hours' => "hour limit exceeded by {$excess}h in {$periodTime}.(Limit:{$limit}H)",
        ]);
    }

    /**
     * Validate shift data against specific business rule.
     * Each child validator must implement its own validation logic.
     *
     * @throws ValidationException
     */
    abstract public function validate(ShiftValidationData $shift): void;
}
