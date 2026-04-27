<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

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
     * @property float|null $hourly_rate The employee's current hourly rate, null if not defined.
     * @property string|null $notes Optional notes or explanation.
     * @property \Illuminate\Support\Carbon $created_at The timestamp when the record was created
     * @property \Illuminate\Support\Carbon $updated_at The timestamp when the record was last updated
     */
    protected $fillable = [
        'user_id',
        'position_id',
        'schedule_id',
        'date',
        'shift_start',
        'shift_end',
        'minutes_worked',
        'hourly_rate',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['scheduled', 'completed']);
    }

    public function scopeWhereOverlapping(Builder $query, string $start, string $end): Builder
    {
        return $query
            ->where('shift_start', '<', $end)
            ->where('shift_end', '>', $start);
    }

    public function scopeExcluding(Builder $query, ?int $id): Builder
    {
        return $query->when($id, fn ($q, $id) => $q->where('id', '!=', $id));
    }

    public function scopeFinishedBefore(Builder $query, string $date, string $time): Builder
    {
        return $query->where(function ($q) use ($date, $time) {
            $q->where('date', '<', $date)
                ->orWhere(function ($inner) use ($date, $time) {
                    $inner->where('date', $date)
                        ->where('shift_end', '<=', $time);
                });
        });
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {

        if ($from && $to) {
            return $query->whereBetween('date', [$from, $to]);
        }

        if ($from && ! $to) {
            return $query->where('date', '>=', $from);
        }

        if (! $from && $to) {
            return $query->where('date', '<=', $to);
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'shift_start' => 'datetime:H:i',
            'shift_end' => 'datetime:H:i',
            'minutes_worked' => 'integer',

        ];
    }
}
