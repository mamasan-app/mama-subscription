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

        // Agregar el rol 'employee' como parte de los datos de creación
        $data['role'] = 'employee';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Asignar el rol 'employee' al usuario recién creado
        $this->record->assignRole('employee');

        // Obtener el store actual mediante getTenant
        $currentStore = Filament::getTenant(); // Asegúrate de que este método devuelve correctamente el store actual

        // Validar si el store actual existe
        if ($currentStore) {
            // Usar attach para asociar el usuario con el store actual, incluyendo el rol 'employee'
            $this->record->stores()->attach($currentStore->id, ['role' => 'employee']);
        }

        
    }


}
