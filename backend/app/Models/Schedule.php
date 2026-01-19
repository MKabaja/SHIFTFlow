<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'month',
        'year',
        'status',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'published_at' => 'datetime',
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
