<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Verifica si se han seleccionado tiendas
        if (isset($data['stores'])) {
            $this->selectedStores = $data['stores'];  // Guardar tiendas seleccionadas temporalmente
            unset($data['stores']);  // Eliminar el campo 'stores' antes de guardar en la tabla 'users'
        }

        // Hash de la contraseña antes de guardar
        $data['password'] = bcrypt($data['password']);
        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole('employee');

        // Si se seleccionaron tiendas, asociarlas con el usuario
        if (!empty($this->selectedStores)) {

            // Asociar las tiendas con el rol 'customer' en la tabla intermedia
            $storesWithRole = [];
            foreach ($this->selectedStores as $storeId) {
                $storesWithRole[$storeId] = ['role' => 'employee'];  // Asignar el rol a cada tienda
            }

            // Sincronizar tiendas con el rol en la tabla intermedia
            $this->record->stores()->sync($storesWithRole);
        }
    }
}
