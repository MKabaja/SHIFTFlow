<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
/**
 * @property int $id
 * @property string $name Position name.
 * @property string|null $description  Explanation of the abbreviation (e.g., B1 -> First Ticket Officer).
 * @property int|null $created_by Manager ID , who created position.
 * @property \Illuminate\Support\Carbon $created_at  The timestamp when the record was created
 * @property \Illuminate\Support\Carbon $updated_at The timestamp when the record was last updated
 * 
 */
    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];
    public function creator(){
    return $this->belongsTo(User::class,'created_by');

}
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class,'position_id');
    }

}
