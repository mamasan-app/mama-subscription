<?php

namespace App\Filament\Store\Resources\ClientesResource\Pages;

use App\Filament\Store\Resources\ClientesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateClientes extends CreateRecord
{
    protected static string $resource = ClientesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hash the password before storing it
        $data['password'] = bcrypt($data['password']);

        // Asegurarse de que 'password_confirmation' no se almacene
        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Verificar si el usuario ya tiene un rol asignado en la tabla intermedia y modificarlo
        $this->record->syncRoles(['customer']);

        // Obtener la tienda actual desde Filament (getTenant)
        $currentStore = Filament::getTenant();

        // Modificar la relación en la tabla intermedia store_user con el rol de 'customer'
        if ($currentStore) {
            // Actualiza el pivote existente con el rol 'customer'
            $this->record->stores()->updateExistingPivot($currentStore->id, ['role' => 'customer']);
        } else {
            // En caso de que no haya una tienda
            dd('No se pudo obtener la tienda actual');
        }
    }

}
