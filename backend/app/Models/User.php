<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\JWT;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     *
     * @property string $name
     * @property string $email
     * @property float $hourly_rate
     * @property int $max_hours_per_month
     * @property int $min_break_hours
     * @property string $contract_type
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'login',
        'pin_hashed',
        'hourly_rate',
        'max_hours_per_month',
        'min_break_hours',
        'contract_type',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_hashed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin_hashed' => 'hashed',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'max_hours_per_month' => 'integer',
            'min_break_hours' => 'integer',
            'role' => 'string',
            'contract_type' => 'string',

        ];
    }

    /**
     * User May have multiple SHIFTS
     *
     * @return HasMany<Shift, User>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'user_id');
    }

    /**
     * User may create multiple SCHEDULES
     *
     * @return HasMany<Schedule, User>
     */
    public function createdSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'created_by');
    }

    /**
     * User may have multiple Availabilities
     *
     * @return HasMany<Availability, User>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class, 'user_id');
    }

    /**
     * User may have mutiple positions & position belong to multiple users
     *
     * @return BelongsToMany<Position, User, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed The primary key of the user (typically an integer or UUID).
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
