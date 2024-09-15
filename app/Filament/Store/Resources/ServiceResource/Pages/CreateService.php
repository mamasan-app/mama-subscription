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

    protected function beforeCreate()
    {
        // Guardar las direcciones seleccionadas por el usuario en la sesión
        $addresses = $this->data['address_id'] ?? [];
        Session::put('address_id', $addresses);
    }

    protected function getRedirectUrl(): string
    {
        // Redirige a la página de listado de servicios después de la creación
        return $this->getResource()::getUrl('index');
    }

}
