<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditStaffProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AccountWidget;
use App\Filament\Widgets\FilamentInfoWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StaffsusPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2.3rem')
            ->id('staffsus')
            ->path('staffsus')
            ->login()
            ->passwordReset()
            ->profile(EditStaffProfile::class, isSimple: false)
            ->authPasswordBroker('staff')
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
            ], isRequired: false)
//            ->colors([
//                'primary' => Color::Sky,
//            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(fn () => __('staffsus/navigation.groups.staff_management')),
            ]);
    }
}
