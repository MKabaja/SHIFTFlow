<?php

namespace App\Services;
use App\Models\User;
use App\Models\Availability;
use App\Models\Schedule;
use Carbon\Carbon;
use Exception;

class ValidationService

{
    protected function getFullDateTime(string $date, string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    }
    public function validateScheduleCreation($userId, $positionId, $date, $shiftStart, $shiftEnd, $maxHoursPerMonth)
    {
        $this->validatePositionPermission($userId, $positionId);
        $this->validateAvailability($userId, $date);
        $this->validateTimeConflict($userId, $date, $shiftStart, $shiftEnd);
        $this->validateMinimumBreak($userId, $date, $shiftStart);
        $this->validateMaxHoursPerMonth($userId, $date, $shiftStart, $shiftEnd, $maxHoursPerMonth);
        
        return true;
    
    }
   
    public function validatePositionPermission($userId, $positionId)

    {   //Retrieve user from database
        $user = User::find($userId);
        //if user doesn exits
        if (!$user) {
            throw new Exception('User not found');
        }
        //get positions from JSON
        $userPositions = $user->positions ?? [];

        //Check if user has permission for this position
        if(!in_array($positionId,$userPositions, true)) {
        throw new Exception('User does not have permission for this position');
        }
        return true;
    }
    public function validateAvailability($userId,$date)
    {
        $availability = Availability::where('user_id',$userId)->where('date',$date)->first();
        
        if($availability && $availability->is_available===false){
            throw new Exception("User is unavailable on {$date}");
        } 
        return true;


    }
    public function validateTimeConflict($userId,$date, $shiftStart, $shiftEnd){

        $conflict=
        Schedule::where('user_id',$userId)
                ->where('date',$date)
                ->where('shift_start','<',$shiftEnd)
                ->where('shift_end','>',$shiftStart)
                ->first();

        if( $conflict){
            throw new Exception('Time conflict: User has schedule during this time'); 
        }
        return true;
    }

     public function validateMinimumBreak($userId,$date, $shiftStart)
     {
        $lastSchedule = 
        Schedule::where('user_id',$userId)
                ->where('date','<',$date)
                ->latest()
                ->first();
        
        

        
        //if there is no previous schedule min break rule is actually done
      if(!$lastSchedule) return true;
        $user = User::find($userId);
        $minBreakHours = $user->min_break_hours;


        // end of last shift(full date)
      $prevShiftEnd = $this->getFullDateTime($lastSchedule->date,$lastSchedule->getRawOriginal('shift_end'));

      //begining of new shift start (full date)
      $currentShiftStart = Carbon::createFromFormat('Y-m-d H:i',$date .' ' . $shiftStart);

      //INCLUDING THE MIDNIGHT CROSSING IN THE PREVIOUS SHIFT
      $lastShiftStartRaw = $lastSchedule->getRawOriginal('shift_start');
      $lastShiftEndRaw = $lastSchedule->getRawOriginal('shift_end');

        // if end time is less than start time
      if($lastShiftEndRaw < $lastShiftStartRaw) {
        $prevShiftEnd->addDay();
      }

      //CALCULATION OF THE DIFFERENCE AND CHECKING THE CONDITION
      $breakMinutes = $prevShiftEnd->diffInMinutes($currentShiftStart,false);
      $breakHours = $breakMinutes / 60;

      if($breakMinutes < 0) {
        throw new Exception('Time conflict: User has schedule during this time');
    }
    if($breakHours < $minBreakHours){
        $requiredHours = number_format($minBreakHours,1);
        $actualHours = number_format($breakHours,1);
        throw new Exception("Insufficient break: required {$requiredHours}h, got {$actualHours}h");
    }
    return true;
}

    public function validateMaxHoursPerMonth($userId, $date, $shiftStart, $shiftEnd, $maxHoursPerMonth)
    {
        //  beginning and end of the month
        $start = Carbon::parse($date)->startOfMonth();
        $end = Carbon::parse($date)->endOfMonth();

        //  Query sum hours_worked in month
        $totalHoursInMonth =
        Schedule::where('user_id',$userId)
                ->whereBetween('date',[$start,$end])
                ->sum('hours_worked');

        //  Oblicz nowe godziny
        $newShiftStartDT = $this->getFullDateTime($date,$shiftStart);

        $newShiftEndDT = $this->getFullDateTime($date,$shiftEnd);

        $newHours = $newShiftStartDT->diffInHours($newShiftEndDT);

        $total = $newHours + $totalHoursInMonth;

        $maxFormatted = number_format($maxHoursPerMonth,1);
        $totalFormatted = number_format($total,1);

        //  checking if  exceeded
        if($maxHoursPerMonth && $total > $maxHoursPerMonth) {
            throw new Exception("Max hours exceeded: {$totalFormatted}h > {$maxFormatted}h");
        }
        return true;
    }

}



                