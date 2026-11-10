<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('SIGEM - TecNM Veracruz')
            ->brandLogo(asset('images/sigem-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/sigem-favicon.svg?v=2'))
            ->font('DM Sans', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap')
            ->colors([
                'primary' => Color::hex('#1b65d4'),
                'success' => Color::hex('#0f9d58'),
                'warning' => Color::hex('#f4a623'),
                'danger' => Color::hex('#d93025'),
                'info' => Color::hex('#0b1d3a'),
                'gray' => Color::hex('#6b7280'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link rel="stylesheet" href="{{ asset(\'css/sigem-theme.css\') }}">
')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => Blade::render('
<div class="sigem-topbar-brand flex items-center gap-x-3">
    <img src="{{ asset(\'images/sigem-logo.svg\') }}" alt="SIGEM" class="h-8">
    <span class="font-bold text-lg hidden sm:block">SIGEM - TecNM Veracruz</span>
</div>
')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => Blade::render('
<div class="flex justify-center items-center gap-4 mb-6">
    <img src="{{ asset(\'images/sigem-logo.svg\') }}" alt="SIGEM" style="height: 40px; object-fit: contain;">
    <img src="{{ asset(\'images/tecnm-logo.png\') }}" alt="TecNM" style="height: 40px; object-fit: contain;">
    <img src="{{ asset(\'images/itv-logo.png\') }}" alt="ITV" style="height: 40px; object-fit: contain;">
</div>
')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => Blade::render('
<div class="text-center mt-6 flex flex-col gap-3">
    <a href="{{ url(\'/servicio-social/login\') }}" class="text-sm font-medium" style="color: #1b65d4; text-decoration: underline;">
        ¿Eres de Servicio Social? Inicia sesión aquí
    </a>
    <a href="{{ url(\'/\') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium">
        &larr; Ir al inicio
    </a>
</div>
')
            )

            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Panel principal')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Gestión de inventario')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Catálogos')
                    ->collapsible(true),
                NavigationGroup::make()
                    ->label('Administración')
                    ->collapsible(true),
            ])
            ->sidebarWidth('20rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
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
            ]);
    }
}
