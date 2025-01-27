<?php

namespace App\Observers;

use App\Models\Plan;

class PlanObserver
{
    public function created(Plan $plan)
    {
        // Obtener las direcciones seleccionadas por el usuario desde la sesión
        $addresses = session('address_id', []);

        if (! empty($addresses)) {
            // Asociar las direcciones al servicio creado
            $plan->addresses()->attach($addresses);
        }

        // Limpiar la sesión
        session()->forget('address_id');
    }
}
