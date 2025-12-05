<?php

namespace App\Services;

use App\Models\User;
use App\Models\Availability;
use App\Models\Schedule;
use Carbon\Carbon;

use Illuminate\Validation\ValidationException;

class ValidationService

{
    /**
     * Summary of getFullDateTime
     * @param string $date
     * @param string $time
     * @return Carbon|null
     */
    public function getFullDateTime(string $date, string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    }

    /**
     * Summary of validateScheduleCreation
     * @param User $user
     * @param int $positionId
     * @param string $date
     * @param string $shiftStart
     * @param string $shiftEnd
     * @return bool
     */
    public function validateScheduleCreation(User $user, int $positionId, string $date, string $shiftStart, string $shiftEnd): bool
    {

        // taking user date from User OBJ to avoid unnecesary DB requests
        $maxHoursPerMonth = $user->max_hours_per_month;
        $minBreakHours = $user->min_break_hours ?? 11;

        //1.Premissions
        $this->validatePositionPermission($user,   $positionId);

        //2.Availability
        $this->validateAvailability($user->id, $date);

        //3.TimeConflict
        $this->validateTimeConflict($user->id, $date, $shiftStart, $shiftEnd);

        //4.MinimumBreak ->minBreakHours from user
        $this->validateMinimumBreak($user->id, $date, $shiftStart, $minBreakHours);

        //Haourly limit
        $this->validateMaxHoursPerMonth($user->id, $date, $shiftStart, $shiftEnd, $maxHoursPerMonth);

        return true;
    }

    /**
     * Summary of validatePositionPermission
     * @param User $user
     * @param int $positionId
     * @return bool
     */
    public function validatePositionPermission(User $user, int $positionId): bool

    {

        //get positions from JSON
        $userPositions = $user->positions ?? [];

        //Check if user has permission for this position
        if (!in_array($positionId, $userPositions,)) {

            throw ValidationException::withMessages([
                'position_id' => ["User does not have permission for this position (ID: $positionId)"]
            ]);
        }
        return true;
    }


    /**
     * Summary of validateAvailability
     * @param int $userId
     * @param string $date
     * @return bool
     */
    public function validateAvailability(int $userId, string $date): bool
    {
        $availability = Availability::where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if ($availability && $availability->is_available === false) {
            throw ValidationException::withMessages([
                'date' => ["User is unavailable on {$date}"]
            ]);
        }
        return true;
    }

    /**
     * Summary of validateTimeConflict
     * @param int $userId
     * @param string $date
     * @param string $shiftStart
     * @param string $shiftEnd
     * @return bool
     */
    public function validateTimeConflict(int $userId, string $date, string $shiftStart, string $shiftEnd): bool
    {

        $conflict =
            Schedule::where('user_id', $userId)
            ->where('date', $date)
            ->where(function ($query) use ($shiftStart, $shiftEnd) {
                $query->where('shift_start', '<', $shiftEnd)
                    ->where('shift_end', '>', $shiftStart);
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'shift_start' => ['Time conflict: User has schedule during this time']
            ]);
        }
        return true;
    }
    /**
     * Summary of validateMinimumBreak
     * @param int $userId
     * @param string $date
     * @param string $shiftStart
     * @param int $minBreakHours
     * @return bool
     */
    public function validateMinimumBreak(int $userId, string $date, string $shiftStart, int $minBreakHours): bool
    {
        $lastSchedule =
            Schedule::where('user_id', $userId)
            ->where('date', '<=', $date)
            ->latest('date')
            ->latest('shift_end')
            ->first();




        //if there is no previous schedule min break rule is actually done
        if (!$lastSchedule) return true;



        // end of last shift
        $prevShiftEnd = $this->getFullDateTime($lastSchedule->date, $lastSchedule->shift_end);


        if ($lastSchedule->shift_end < $lastSchedule->shift_start) {
            $prevShiftEnd->addDay();
        }


        //begining of new shift start 
        $currentShiftStart = $this->getFullDateTime($date, $shiftStart);

        $breakHours = $prevShiftEnd->diffInMinutes($currentShiftStart, false) / 60;

        if ($breakHours < 0) {
            return true;
        }

        if ($breakHours < $minBreakHours) {
            $required = number_format($minBreakHours, 1);
            $actual = number_format($breakHours, 1);
            throw ValidationException::withMessages([
                'min_break' => ["Insufficient break: required {$required}h, got {$actual}h"]
            ]);
        }


        return true;
    }

    /**
     * Summary of validateMaxHoursPerMonth
     * @param int $userId
     * @param string $date
     * @param string $shiftStart
     * @param string $shiftEnd
     * @param int $maxHoursPerMonth
     * @return bool
     */
    public function validateMaxHoursPerMonth(int $userId, string $date, string $shiftStart, string $shiftEnd, int $maxHoursPerMonth): bool
    {
        if (!$maxHoursPerMonth) return true;
        //  beginning and end of the month
        $start = Carbon::parse($date)->startOfMonth();
        $end = Carbon::parse($date)->endOfMonth();

        //  Query sum hours_worked in month
        $totalMinutesInMonth =
            Schedule::where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->sum('hours_worked');


        $newShiftStart = $this->getFullDateTime($date, $shiftStart);

        $newShiftEnd = $this->getFullDateTime($date, $shiftEnd);
        if ($shiftEnd < $shiftStart) {
            $newShiftEnd->addDay();
        }

        $newMinutes = $newShiftStart->diffInMinutes($newShiftEnd);

        $total = $totalMinutesInMonth + $newMinutes;
        $maxMinutesPerMonth = $maxHoursPerMonth * 60;
        $totalHours = $total / 60;

        if ($total > $maxMinutesPerMonth) {
            $formattedTotal = number_format($totalHours, 1);
            throw ValidationException::withMessages([
                'max_hours' => ["Max hours exceeded: {$formattedTotal}h > {$maxHoursPerMonth}h"]
            ]);
        }

        return true;
    }
}
