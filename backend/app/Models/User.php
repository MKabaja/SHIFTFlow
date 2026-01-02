<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
     * Summary of schedules
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Schedule, User>
     *                                                                         User may have multiple schedules
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'user_id');
    }

    /**
     * Summary of availabilities
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Availability, User>
     *                                                                             User may have multiple availabilities
     */
    public function availabilities()
    {
        return $this->hasMany(Availability::class, 'user_id');
    }

    /**
     * Summary of positions
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Position, User, \Illuminate\Database\Eloquent\Relations\Pivot>
     *                                                                                                                              user many belong to many Positions
     */
    public function positions()
    {
        return $this->belongsToMany(Position::class);
    }
    /**
     * ========== JWT METHODS ==========
     *
     * Te dwie metody są wymagane przez JWTSubject
     * Mówią JWT'owi jak identyfikować usera w tokenie
     */

    /**
     * Zwróć ID usera (do JWT payload)
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // zwróć custom claims do JWT
    public function getJWTCustomClaims()
    {
        return [];
    }
}
