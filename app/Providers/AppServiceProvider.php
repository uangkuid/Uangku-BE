<?php

namespace App\Providers;

use App\Auth\CachedEloquentStaffProvider;
use App\Exceptions\EncryptionException;
use BezhanSalleh\LanguageSwitch\Events\LocaleChanged;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Color\Hex;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->assertMinimumPepperLength();

        Auth::provider('cached_eloquent', function ($app, array $config) {
            return new CachedEloquentStaffProvider($app['hash'], $config['model']);
        });

        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->mixedCase()
                    ->symbols()
                    ->uncompromised()
                : $rule;
        });

        DB::listen(function ($query) {
            Log::debug("SQL: {$query->sql}, Bindings: ".json_encode($query->bindings));
        });

        /**
         * Setup Filament
         */
        Table::configureUsing(function (Table $table): void {
            $table
                ->deferLoading()
                ->paginationPageOptions([10, 25, 50]);
        });
        FilamentColor::register([
            'primary' => [
                50 => Hex::fromString('#C7E7FA')->toRgb(),
                100 => Hex::fromString('#A6D9F5')->toRgb(),
                200 => Hex::fromString('#87CAF0')->toRgb(),
                300 => Hex::fromString('#68BBEA')->toRgb(),
                400 => Hex::fromString('#4AACE3')->toRgb(),
                500 => Hex::fromString('#2D9CDC')->toRgb(),
                600 => Hex::fromString('#1E81B9')->toRgb(),
                700 => Hex::fromString('#116594')->toRgb(),
                800 => Hex::fromString('#08476B')->toRgb(),
                900 => Hex::fromString('#03293F')->toRgb(),
            ],
        ]);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch
                ->locales(['en', 'id'])
                ->userPreferredLocale(fn () => Auth::guard('web')->user()?->locale);
        });

        Event::listen(LocaleChanged::class, function (LocaleChanged $event): void {
            Auth::guard('web')->user()?->update(['locale' => $event->locale]);
        });
    }

    /**
     * A configured-but-too-short MAIN_SALT_KEY silently defeats PIN/authKey
     * hashing (see docs/encryption.md §4 — a 72+ byte pepper used to truncate
     * the secret entirely out of the bcrypt input before EncryptionHelper's
     * HMAC pre-hash fix). Fail fast at boot rather than only when first used.
     *
     * Only rejects keys that ARE set but too weak — an unset key is left to
     * the lazy `empty()` checks in EncryptionHelper, so commands that run
     * before secrets are provisioned (e.g. `migrate` in a fresh CI env) don't
     * break.
     *
     * @throws EncryptionException
     */
    private function assertMinimumPepperLength(): void
    {
        foreach (['MAIN_SALT_KEY', 'MAIN_SYSTEM_KEY', 'MAIN_BLIND_INDEX_KEY'] as $key) {
            $value = env($key);
            if (! empty($value) && strlen($value) < 32) {
                throw new EncryptionException("{$key} must be at least 32 characters long.");
            }
        }
    }
}
