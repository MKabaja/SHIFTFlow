<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property bool $is_available
 * @property \Illuminate\Support\Carbon|null $submission_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereSubmissionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Availability whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    protected $fillable = [
        'date',
        'is_available',
        'notes',
        'user_id',

    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'submission_date' => 'date',
            'is_available' => 'boolean',
        ];
    }
}
