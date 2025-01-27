<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar el documento de identidad si los prefijos están presentes
        if (isset($data['identity_prefix'], $data['identity_number'])) {
            $data['identity_document'] = $data['identity_prefix'].'-'.$data['identity_number'];
            unset($data['identity_prefix'], $data['identity_number']);
        }

        // Verificar unicidad del correo electrónico
        if (User::where('email', $data['email'])->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El correo electrónico ya está registrado.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'email' => 'El correo electrónico ya está registrado.',
            ]);
        }

        // Verificar unicidad del número de teléfono
        if (! empty($data['phone_number']) && User::where('phone_number', $data['phone_number'])->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El número de teléfono ya está registrado.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'phone_number' => 'El número de teléfono ya está registrado.',
            ]);
        }

        // Verificar unicidad del documento de identidad
        if (isset($data['identity_document']) && User::where('identity_document', $data['identity_document'])->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El documento de identidad ya está registrado.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'identity_document' => 'El documento de identidad ya está registrado.',
            ]);
        }

        // Resto del código...

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
                ->body('No se pudo asignar el rol de empleado: '.$e->getMessage())
                ->danger()
                ->send();
            abort(500, 'No se pudo asignar el rol de empleado.');
        }

        // Obtener la tienda actual mediante getTenant
        $currentStore = Filament::getTenant();

        // Validar si la tienda actual existe
        if (! $currentStore) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo identificar la tienda actual.')
                ->danger()
                ->send();
            abort(500, 'No se pudo identificar la tienda actual.');
        }

        // Asociar el empleado a tiendas adicionales seleccionadas
        if (! empty($this->selectedStores)) {
            try {
                $storesWithRole = [];
                foreach ($this->selectedStores as $storeId) {
                    $storesWithRole[$storeId] = ['role' => 'employee'];
                }
                $this->record->stores()->syncWithoutDetaching($storesWithRole);
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error')
                    ->body('No se pudo asociar el empleado a las tiendas seleccionadas: '.$e->getMessage())
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
