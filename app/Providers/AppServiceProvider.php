<?php

namespace App\Providers;

use App\Auth\CachedEloquentStaffProvider;
use App\Models\StaffAccount;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            Log::debug("SQL: {$query->sql}, Bindings: " . json_encode($query->bindings));
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
    }
}
