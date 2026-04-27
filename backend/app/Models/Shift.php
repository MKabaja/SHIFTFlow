<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $schedule_id
 * @property \Illuminate\Support\Carbon $date
 * @property int $position_id
 * @property \Illuminate\Support\Carbon $shift_start
 * @property \Illuminate\Support\Carbon $shift_end
 * @property int|null $minutes_worked
 * @property string $status
 * @property numeric|null $hourly_rate
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Position $position
 * @property-read \App\Models\Schedule|null $schedule
 * @property-read \App\Models\User $user
 *
 * @method static Builder<static>|Shift active()
 * @method static Builder<static>|Shift dateRange(?string $from, ?string $to)
 * @method static Builder<static>|Shift excluding(?int $id)
 * @method static \Database\Factories\ShiftFactory factory($count = null, $state = [])
 * @method static Builder<static>|Shift finishedBefore(string $date, string $time)
 * @method static Builder<static>|Shift newModelQuery()
 * @method static Builder<static>|Shift newQuery()
 * @method static Builder<static>|Shift query()
 * @method static Builder<static>|Shift whereCreatedAt($value)
 * @method static Builder<static>|Shift whereDate($value)
 * @method static Builder<static>|Shift whereHourlyRate($value)
 * @method static Builder<static>|Shift whereId($value)
 * @method static Builder<static>|Shift whereMinutesWorked($value)
 * @method static Builder<static>|Shift whereNotes($value)
 * @method static Builder<static>|Shift whereOverlapping(string $start, string $end)
 * @method static Builder<static>|Shift wherePositionId($value)
 * @method static Builder<static>|Shift whereScheduleId($value)
 * @method static Builder<static>|Shift whereShiftEnd($value)
 * @method static Builder<static>|Shift whereShiftStart($value)
 * @method static Builder<static>|Shift whereStatus($value)
 * @method static Builder<static>|Shift whereUpdatedAt($value)
 * @method static Builder<static>|Shift whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Shift extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function schedule(): BelongsTo
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
