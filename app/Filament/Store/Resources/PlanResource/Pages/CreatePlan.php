<?php

namespace App\Filament\Store\Resources\PlanResource\Pages;

use App\Filament\Store\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Plan;
use Illuminate\Support\Facades\Session;
use Filament\Facades\Filament;


class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validar que el precio no sea nulo antes de convertirlo a centavos
        $data['price_cents'] = isset($data['price']) ? $data['price'] * 100 : 0;

        // Guardar las direcciones seleccionadas por el usuario en la sesión
        $addresses = $data['address_id'] ?? [];
        Session::put('address_id', $addresses);

        // Obtener el store_id desde el sistema de multitenancy (Tenant)
        $currentStore = Filament::getTenant();

        if ($currentStore) {
            // Si se obtuvo el tenant, asignar el store_id al plan
            $data['store_id'] = $currentStore->id;
        }

        // Manejo de la lógica de duración infinita o finita
        if ($data['infinite_duration'] ?? false) {
            $data['duration'] = null; // Si es infinito, la duración será nula
        }

        return $data;
    }

}
