<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

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
        // Asignar el rol 'employee' al usuario recién creado
        $this->record->assignRole('employee');

        // Obtener el store actual mediante getTenant
        $currentStore = Filament::getTenant(); // O usa tu método getTenant()

        // Asegurar que se asigna el store actual al usuario
        if ($currentStore) {
            $this->record->stores()->attach($currentStore->id, ['role' => 'employee']);
        }

        // Si se seleccionaron tiendas adicionales, asociarlas también
        if (!empty($this->selectedStores)) {
            $storesWithRole = [];
            foreach ($this->selectedStores as $storeId) {
                $storesWithRole[$storeId] = ['role' => 'employee'];
            }
            $this->record->stores()->syncWithoutDetaching($storesWithRole);
        }
    }

}
