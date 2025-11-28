<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    /**
     * Summary of fillable
     * @var array
     * @property int $id
     * @property \Illuminate\Support\Carbon $date  The date the availability applies to
     * @property bool $is_available true by default
     * @property string|null $notes
     * @property int $user_id
     * @property \Illuminate\Support\Carbon $created_at  The timestamp when the record was created
     * @property \Illuminate\Support\Carbon $updated_at The timestamp when the record was last updated
     * @property \Illuminate\Support\Carbon $submission_date Backend-only
     * 
     */
    protected $fillable = [
        'date',
        'is_available',
        'notes',
        'user_id',
        
    ];
    protected function casts(): array
    {
        return [
            'date'=> 'date',
            'submission_date'=> 'date',
            'is_available' => 'boolean'
        ] ;
    }

    public function user(){
    return $this->belongsTo(User::class,'user_id');
}
}
