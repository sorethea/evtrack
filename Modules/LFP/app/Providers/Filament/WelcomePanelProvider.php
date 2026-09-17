<?php

namespace Modules\LFP\Providers\Filament;

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

class WelcomePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $separator = DIRECTORY_SEPARATOR;
        return $panel
            ->id('lfp-welcome')
            ->path('lfp/welcome')
            ->brandName($this->getNavigationLabel())
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: module("LFP", true)->appPath("Filament{$separator}LFPWelcome{$separator}Resources"), for: module("LFP", true)->appNamespace('Filament\LFPWelcome\Resources'))
            ->discoverPages(in:module("LFP", true)->appPath("Filament{$separator}LFPWelcome{$separator}Pages"), for: module("LFP", true)->appNamespace('Filament\LFPWelcome\Pages'))
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in:module("LFP", true)->appPath("Filament{$separator}LFPWelcome{$separator}Widgets"), for: module("LFP", true)->appNamespace('Filament\LFPWelcome\Widgets'))
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->discoverClusters(in: module("LFP", true)->appPath("Filament{$separator}LFPWelcome{$separator}Clusters"), for: module("LFP", true)->appNamespace('Filament\LFPWelcome\Clusters'))
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
                \Filament\Navigation\NavigationItem::make()
                    ->label(__('Back Home'))
                    ->sort(-1000)
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedHomeModern)
                    ->url(filament()->getDefaultPanel()->getUrl()),
            ]);
    }

    public function getNavigationLabel(): string
    {
        return __("Welcome to LFP Health Test");
    }
}
