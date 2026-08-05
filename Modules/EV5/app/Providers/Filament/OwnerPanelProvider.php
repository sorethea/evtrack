<?php

namespace Modules\EV5\Providers\Filament;

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

class OwnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $separator = DIRECTORY_SEPARATOR;
        return $panel
            ->id('ev5-owner')
            ->path('ev5/owner')
            ->brandName($this->getNavigationLabel())
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: module("EV5", true)->appPath("Filament{$separator}EV5Owner{$separator}Resources"), for: module("EV5", true)->appNamespace('Filament\EV5Owner\Resources'))
            ->discoverPages(in:module("EV5", true)->appPath("Filament{$separator}EV5Owner{$separator}Pages"), for: module("EV5", true)->appNamespace('Filament\EV5Owner\Pages'))
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in:module("EV5", true)->appPath("Filament{$separator}EV5Owner{$separator}Widgets"), for: module("EV5", true)->appNamespace('Filament\EV5Owner\Widgets'))
            ->widgets([
                AccountWidget::class,
                //FilamentInfoWidget::class,
            ])
            ->discoverClusters(in: module("EV5", true)->appPath("Filament{$separator}EV5Owner{$separator}Clusters"), for: module("EV5", true)->appNamespace('Filament\EV5Owner\Clusters'))
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
        return __("My EV");
    }
}
