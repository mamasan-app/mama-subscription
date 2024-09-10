<?php

declare(strict_types=1);

namespace App\Http\Middleware\Store;

use App\Filament\Store\Pages\Billing;
use App\Models\Store;
use App\Providers\Filament\StorePanelPanelProvider;
use Closure;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Store|null $store */
        $store = Filament::getTenant();  // Obtener la tienda actual

        // Verificar si el panel actual es el panel de tiendas
        if (Filament::getCurrentPanel()?->getId() !== StorePanelPanelProvider::PANEL_ID) {
            return $next($request);
        }

        // Si no hay tienda, proceder normalmente
        if (!$store) {
            return $next($request);
        }

        // Verificar el estado de la suscripción de la tienda
        if (
            !$store->subscription ||
            (!$store->subscription->is_active &&
                !$store->subscription->is_on_trial &&
                !$store->subscription->is_past_due)
        ) {

            // Redirigir al usuario a la página de facturación si no tiene una suscripción válida
            return redirect()->to(Billing::getUrl(tenant: $store));
        }

        return $next($request);  // Permitir la continuación de la solicitud si la tienda tiene una suscripción activa
    }
}
