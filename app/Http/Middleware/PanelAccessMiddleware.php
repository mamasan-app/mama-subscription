<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Panel;
use Filament\Facades\Filament;

class PanelAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login'); // Redirigir al login si no está autenticado
        }

        // Obtener el ID del panel desde la ruta o el proveedor de Filament
        $panelId = $request->route('panelId') ?? $request->segment(1);  // Asumiendo que 'admin' o 'app' es el primer segmento de la URL

        if (!$panelId) {
            abort(404, "Panel ID not found in the route.");
        }

        // Obtener el panel usando Filament
        $panel = Filament::getPanel($panelId);

        //dd($panelId, auth()->user()->roles->pluck('name'));

        // Verificar si el panel existe y el usuario tiene permiso para acceder
        if ($panel && auth()->user()->canAccessPanel($panel)) {
            return $next($request);
        }

        // Si el usuario no tiene acceso, devolver una respuesta 403
        return response()->json(['error' => 'Unauthorized'], 403);
    }

}
