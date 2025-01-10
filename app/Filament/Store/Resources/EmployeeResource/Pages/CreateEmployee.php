<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar el documento de identidad si los prefijos están presentes
        if (isset($data['identity_prefix'], $data['identity_number'])) {
            $data['identity_document'] = $data['identity_prefix'] . '-' . $data['identity_number'];
            unset($data['identity_prefix'], $data['identity_number']); // Eliminar los campos originales
        }

        // Verificar unicidad del correo electrónico
        if (User::where('email', $data['email'])->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El correo electrónico ya está registrado.')
                ->danger()
                ->send();
            abort(422, 'El correo electrónico ya está registrado.');
        }

        // Verificar unicidad del número de teléfono (si está presente)
        if (!empty($data['phone_number']) && User::where('phone_number', $data['phone_number'])->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El número de teléfono ya está registrado.')
                ->danger()
                ->send();
            abort(422, 'El número de teléfono ya está registrado.');
        }

        // Verificar unicidad del documento de identidad
        if (isset($data['identity_document']) && User::where('identity_document', $data['identity_document'])->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El documento de identidad ya está registrado.')
                ->danger()
                ->send();
            abort(422, 'El documento de identidad ya está registrado.');
        }

        // Verificar si se han seleccionado tiendas
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
        try {
            // Asignar el rol 'employee' al usuario recién creado
            $this->record->assignRole('employee');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo asignar el rol de empleado: ' . $e->getMessage())
                ->danger()
                ->send();
            abort(500, 'No se pudo asignar el rol de empleado.');
        }

        // Obtener la tienda actual mediante getTenant
        $currentStore = Filament::getTenant();

        // Validar si la tienda actual existe
        if (!$currentStore) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo identificar la tienda actual.')
                ->danger()
                ->send();
            abort(500, 'No se pudo identificar la tienda actual.');
        }

        // Asociar el empleado a tiendas adicionales seleccionadas
        if (!empty($this->selectedStores)) {
            try {
                $storesWithRole = [];
                foreach ($this->selectedStores as $storeId) {
                    $storesWithRole[$storeId] = ['role' => 'employee'];
                }
                $this->record->stores()->syncWithoutDetaching($storesWithRole);
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error')
                    ->body('No se pudo asociar el empleado a las tiendas seleccionadas: ' . $e->getMessage())
                    ->danger()
                    ->send();
                abort(500, 'No se pudo asociar el empleado a las tiendas seleccionadas.');
            }
        }

        Notification::make()
            ->title('Empleado creado')
            ->body('El empleado fue registrado exitosamente.')
            ->success()
            ->send();
    }
}