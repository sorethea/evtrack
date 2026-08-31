<?php

namespace Modules\SA\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\SA\Filament\Widgets\Battery;

class SolarPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $separator = DIRECTORY_SEPARATOR;
        return $panel
            ->id('sa-solar')
            ->path('sa/solar')
            ->brandName($this->getNavigationLabel())
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: module("SA", true)->appPath("Filament{$separator}SASolar{$separator}Resources"), for: module("SA", true)->appNamespace('Filament\SASolar\Resources'))
            ->discoverPages(in:module("SA", true)->appPath("Filament{$separator}SASolar{$separator}Pages"), for: module("SA", true)->appNamespace('Filament\SASolar\Pages'))
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in:module("SA", true)->appPath("Filament{$separator}SASolar{$separator}Widgets"), for: module("SA", true)->appNamespace('Filament\SASolar\Widgets'))
            ->widgets([
                Battery::class,
                //AccountWidget::class,
                //FilamentInfoWidget::class,
            ])
            ->discoverClusters(in: module("SA", true)->appPath("Filament{$separator}SASolar{$separator}Clusters"), for: module("SA", true)->appNamespace('Filament\SASolar\Clusters'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->navigationItems([
                // Add a backlink to the default panel
//                \Filament\Navigation\NavigationItem::make()
//                    ->label(__('Back Home'))
//                    ->sort(-1000)
//                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedHomeModern)
//                    ->url(filament()->getDefaultPanel()->getUrl()),
            ]);
    }

    public function getNavigationLabel(): string
    {
        return __("Solar Assistant");
    }
}
