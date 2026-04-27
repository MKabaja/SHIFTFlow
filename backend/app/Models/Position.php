<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    /**
     * @property int $id
     * @property string $name Position name.
     * @property string|null $description Explanation of the abbreviation (e.g., B1 -> First Ticket Officer).
     * @property int|null $created_by Manager ID , who created position.
     * @property \Illuminate\Support\Carbon $created_at The timestamp when the record was created
     * @property \Illuminate\Support\Carbon $updated_at The timestamp when the record was last updated
     */
    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    /**
     * shows info abaut creator
     *
     * @return BelongsTo<User, Position>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * shcedule may have multiple positions
     *
     * @return HasMany<Schedule, Position>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'position_id');
    }

    /**
     * Position may belong to many users
     *
     * @return BelongsToMany<User, Position, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
