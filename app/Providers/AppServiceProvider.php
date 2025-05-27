<?php

namespace App\Providers;

use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        Table::configureUsing(function (Table $table): void {
            $table
                ->deferLoading()
                ->paginationPageOptions([10, 25, 50]);
        });
    }
}
