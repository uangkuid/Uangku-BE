<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use App\Observers\StaffAccountObserver;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
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
class StaffAccount extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar, HasEmailAuthentication, JWTSubject
{
    use HasFactory, HasRoles, HasUuids, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, InteractsWithEmailAuthentication, Notifiable;

    /**
     * Guard yang dipakai spatie/permission untuk role & permission staff.
     * Wajib 'web' karena StaffAccount adalah provider guard 'web' (bukan 'api'/User).
     */
    protected string $guard_name = 'web';

    /**
     * Hanya staff dengan role/permission Shield yang valid yang boleh masuk panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->roles()->exists();
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
        'avatar',
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

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return StorageHelper::temporaryUrl('minio', $this->avatar, now()->addMinutes(60));
    }

    protected $defaultSelect = [
        'id',
        'name',
        'email',
        'password',
        'avatar',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'has_email_authentication',
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
