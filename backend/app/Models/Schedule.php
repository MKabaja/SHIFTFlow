<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{

/**
* The attributes that are mass assignable.
/**
 * @property int $id
 * @property int $user_id ID of the employee this schedule belongs to (BelongsTo User).
 * @property \Illuminate\Support\Carbon $date The date the shift is scheduled for.
 * @property string $position_id Position code in the mine (e.g., B1, WR).
 * @property \Illuminate\Support\Carbon $shift_start Shift start time
 * @property \Illuminate\Support\Carbon $shift_end Shift end time
 * @property int|null $hours_worked Calculated work hours (by backend).
 * @property string $status Shift status (scheduled, completed, cancelled, vacation, unavailable).
 * @property float|null $hourly_rate  The employee's current hourly rate, null if not defined.
 * @property string|null $notes  Optional notes or explanation.
 * @property \Illuminate\Support\Carbon $created_at  The timestamp when the record was created
 * @property \Illuminate\Support\Carbon $updated_at The timestamp when the record was last updated
 */
     
 protected $fillable = [
    'user_id', 
    'date', 
    'position_id',
    'shift_start',
    'shift_end',
    'hours_worked',
    'status',
    'hourly_rate',
    'notes',
 ];
 protected function casts(): array
    {
        return [
            'date'=> 'date',
            'shift_start' => 'datetime:H:i',
            'shift_end' => 'datetime:H:i'
        ];
    }

public function user(){
    return $this->belongsTo(User::class,'user_id');
}
public function position()
{
    return $this->belongsTo(Position::class,'position_id');
}


}
