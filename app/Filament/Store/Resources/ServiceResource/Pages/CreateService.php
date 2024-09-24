<?php

namespace App\Filament\Store\Resources\ServiceResource\Pages;

use App\Filament\Store\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Service;
use Illuminate\Support\Facades\Session;
use Filament\Facades\Filament;


class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convierte el precio en dólares a centavos
        $data['price_cents'] = $data['price'] * 100;

        // Guardar las direcciones seleccionadas por el usuario en la sesión
        $addresses = $data['address_id'] ?? [];
        Session::put('address_id', $addresses);

        // Obtener el store_id desde el sistema de multitenancy (Tenant)
        $currentStore = Filament::getTenant();

        if ($currentStore) {
            // Si se obtuvo el tenant, asignar el store_id al servicio
            $data['store_id'] = $currentStore->id;
        }

        return $data;
    }

}
