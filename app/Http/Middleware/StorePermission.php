<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Store;
use App\Providers\Filament\StorePanelPanelProvider;
use Closure;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorePermission
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
        // Verificar si el usuario está autenticado
        if (empty(auth()->user())) {
            return $next($request);
        }

        // Verificar si Filament está sirviendo una solicitud
        if (!Filament::isServing()) {
            return $next($request);
        }

        // Verificar si estamos en el panel de tiendas
        if (Filament::getCurrentPanel()?->getId() !== 'store') {
            return $next($request);
        }

        // Obtener la tienda actual desde Filament
        /** @var Store|null $store */
        $store = Filament::getTenant();

        // Si hay una tienda, configuramos el ID para los permisos
        if (!empty($store)) {
            setPermissionsTeamId($store->id);  // Configurar permisos basados en la tienda actual
        }

        return $next($request);
    }
}
