<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
 protected $fillable = [
    'user_id', //front wysyła
    'date', //front wysyła
    'position',//front wysyła
    'shift_start',//front wysyła
    'shift_end',//front wysyła
    'hours_worked',//backend oblicza i wstawia
    'status',//front wysyła
    'hourly_rate',//front wysyła
    'notes',//front wysyła
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


}
