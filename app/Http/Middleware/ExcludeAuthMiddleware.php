<?php

namespace App\Http\Middleware;

use Closure;

class ExcludeAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        // Permitir todas las rutas que comienzan con "api/"
        if ($request->is('api/*')) {
            //dd('Middleware ejecutado correctamente');
            return $next($request);
        }

        return abort(403, 'Unauthorized');
    }
}

