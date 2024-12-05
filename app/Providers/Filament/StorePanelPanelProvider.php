<?php

namespace App\Providers\Filament;

use App\Models\Store;
use App\Models\User;
use App\Filament\Pages\Settings;
use App\Filament\Store\Pages\Dashboard;
use App\Filament\Store\Pages\EditStoreProfile;
use App\Filament\Store\Pages\Auth\EditProfile;
use App\Filament\Store\Pages\RegisterStore;
use App\Filament\Store\Billing\BillingProvider;
use App\Http\Middleware\RouteToMainRegisterRoute;
use App\Http\Middleware\StorePermission;
use App\Filament\Pages\Auth\UserRegister;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;


class StorePanelPanelProvider extends PanelProvider
{
    const string PANEL_ID = 'store';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id(self::PANEL_ID)
            ->path('/tienda')
            //->brandLogo(fn() => view('filament.logo'))  // Puedes crear un logo específico para la tienda
            ->brandName('Negocio')
            ->favicon(asset('favicon.ico'))
            ->login()
            ->loginRouteSlug('ingresar')
            ->registration(UserRegister::class)  // Página de registro de tiendas
            ->registrationRouteSlug('registrar')
            ->passwordReset()
            ->passwordResetRequestRouteSlug('restablecer-password')
            ->emailVerification()
            ->emailVerificationPromptRouteSlug('verificar-email')
            ->profile(EditProfile::class)
            ->databaseNotifications()
            ->tenant(model: Store::class, slugAttribute: 'slug')  // Relación de tenant con tienda
            //->tenantDomain('{tenant:slug}.mama-subscription.localhost')  // Manejo de subdominio basado en tienda
            ->tenantRegistration(RegisterStore::class)  // Registro de tienda
            ->tenantProfile(EditStoreProfile::class)  // Página de perfil de tienda
            //->requiresTenantSubscription()  // Si las tiendas tienen subscripciones
            //->tenantBillingProvider(new BillingProvider)
            //->tenantBillingRouteSlug('subscripcion')
            ->tenantMenuItems([
                'billing' => MenuItem::make()
                    ->visible(fn() => auth()->user()?->can('manageSubscription', Store::class)),  // Permisos para manejar la subscripción
                'register' => MenuItem::make()->label('Registrar una nueva Tienda'),
                'profile' => MenuItem::make()->label('Editar Perfil de la Tienda'),
            ])
            ->unsavedChangesAlerts()
            ->databaseTransactions()
            ->colors([
                'primary' => Color::Purple,  // Personaliza el color para la tienda
            ])
            //->viteTheme('resources/css/filament/store/theme.css')  // Define tu propio archivo CSS si es necesario
            ->discoverResources(in: app_path('Filament/Store/Resources'), for: 'App\\Filament\\Store\\Resources')
            ->discoverPages(in: app_path('Filament/Store/Pages'), for: 'App\\Filament\\Store\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Store/Widgets'), for: 'App\\Filament\\Store\\Widgets')
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
                //RouteToMainRegisterRoute::class,  // Middleware que redirige si la tienda no está registrada
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantMiddleware([
                StorePermission::class,  // Middleware para gestionar permisos de tienda
            ], isPersistent: true);
    }
}
