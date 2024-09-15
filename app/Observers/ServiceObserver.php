<?php

namespace App\Observers;

use App\Models\Service;

class ServiceObserver
{
    public function created(Service $service)
    {
        // Obtener las direcciones seleccionadas por el usuario desde la sesión
        $addresses = session('address_id', []);

        if (!empty($addresses)) {
            // Asociar las direcciones al servicio creado
            $service->addresses()->attach($addresses);
        }

        // Limpiar la sesión
        session()->forget('address_id');
    }
}

