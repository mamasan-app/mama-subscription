<?php

namespace App\Filament\Store\Resources\ServiceResource\Pages;

use App\Filament\Store\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Service;
use Illuminate\Support\Facades\Session;


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

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Redirige a la página de listado de servicios después de la creación
        return $this->getResource()::getUrl('index');
    }

}
