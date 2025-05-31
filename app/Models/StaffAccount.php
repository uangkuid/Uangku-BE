<?php

namespace App\Models;

use App\Observers\StaffAccountObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[ObservedBy([StaffAccountObserver::class])]
class StaffAccount extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            "data" => [
                "staff_id" => $this->id
            ]
        ];
    }

    public function featureStatuses(): HasMany
    {
        return $this->hasMany(FeatureStatus::class, 'updated_by');
    }

    protected $defaultSelect = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'created_at',
        'updated_at'
    ]; // customize sesuai kebutuhan

    public function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::newQuery()->select($this->defaultSelect);
    }

    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes()->select($this->defaultSelect);
    }
}
