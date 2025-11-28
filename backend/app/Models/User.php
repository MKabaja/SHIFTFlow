<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\JWT;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     * @property string $name
     * @property string $email
     * @property array<string> $positions
     * @property float $hourly_rate
     * @property int $max_hours_per_month
     * @property int $min_break_hours
     * @property string $contract_type
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'positions',
        'hourly_rate',
        'max_hours_per_month',
        'min_break_hours',
        'contract_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_hashed'
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
            'positions' => 'array',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'max_hours_per_month' => 'integer',
            'min_break_hours' => 'integer',
            'role' => 'string',
            'contract_type' => 'string',
            
        ];
    }
    protected function pinHashed():Attribute
    {
        return Attribute::make(
            set: fn($value) => $value ? Hash::make($value) : null,
        );
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class,'user_id');
    }
    public function availabilities()
    {
        return $this->hasMany(Availability::class,'user_id');
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

    //zwróć custom claims do JWT
    public function getJWTCustomClaims()
    {
        return [];
    }
}
