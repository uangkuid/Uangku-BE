<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'status',
        'suspended_at',
        'suspended_reason',
    ];

    // Kolom baru WAJIB ada di sini, jika tidak newQuery() akan men-drop-nya.
    protected $defaultSelect = [
        'id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'status',
        'suspended_at',
        'suspended_reason',
        'avatar',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function newQuery(): Builder
    {
        return parent::newQuery()->select($this->defaultSelect);
    }

    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes()->select($this->defaultSelect);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
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
            'status' => UserStatus::class,
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
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
            'data' => [
                'user_id' => $this->id,
            ],
        ];
    }

    public function familyAccess(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'users');
    }

    public function userKey(): HasMany
    {
        return $this->hasMany(UserKey::class, 'users');
    }

    public function walletAccess(): HasMany
    {
        return $this->hasMany(WalletAccess::class, 'users');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'users');
    }

    public function wallets()
    {
        return $this->hasManyThrough(Wallet::class, WalletAccess::class, 'users', 'id', 'id', 'wallets');
    }
}
