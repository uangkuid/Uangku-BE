<?php

namespace App\Models;

use App\Observers\StaffAccountObserver;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy([StaffAccountObserver::class])]
class StaffAccount extends Authenticatable implements FilamentUser, JWTSubject
{
    use HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * Guard yang dipakai spatie/permission untuk role & permission staff.
     * Wajib 'web' karena StaffAccount adalah provider guard 'web' (bukan 'api'/User).
     */
    protected string $guard_name = 'web';

    /**
     * Hanya staff dengan role/permission valid yang boleh masuk panel.
     * Fallback legacy `role === 'admin'` menjaga admin lama tetap bisa masuk
     * selama masa transisi; hapus setelah semua staff punya role Shield.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->roles()->exists() || $this->role === 'admin';
    }

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
            'data' => [
                'staff_id' => $this->id,
            ],
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
        'updated_at',
    ]; // customize sesuai kebutuhan

    public function newQuery(): Builder
    {
        return parent::newQuery()->select($this->defaultSelect);
    }

    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes()->select($this->defaultSelect);
    }

    // Override untuk invalidate cache saat update
    public function save(array $options = [])
    {
        $result = parent::save($options);
        Cache::forget('staff.'.$this->id);

        return $result;
    }

    public function delete()
    {
        Cache::forget('staff.'.$this->id);

        return parent::delete();
    }
}
