<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RouteToMainRegisterRoute
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $uri = $request->getRequestUri();

        /** @var string $rootDomain */
        $rootDomain = config('mama-subscription.localhost');  // Verificar si estamos en el dominio principal

        if ($host === $rootDomain) {
            return $next($request);  // Si estamos en el dominio principal, continuar con la solicitud
        }

        // Verificar si la ruta es la de registro en el panel de tiendas
        if ($request->route()?->getName() === 'filament.store.auth.register') {
            /** @var string $appUrl */
            $appUrl = config('mama-subscription.localhost');  // URL del dominio principal configurada en el archivo .env

            // Redirigir al dominio principal con la misma URI
            return redirect()->to($appUrl.$uri);
        }

        return $next($request);  // Si no hay redirección, continuar con la solicitud
    }
}
