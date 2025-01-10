<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Separar el prefijo y el número de la cédula
        if (!empty($data['identity_document'])) {
            [$data['identity_prefix'], $data['identity_number']] = explode('-', $data['identity_document']);
        }

        // Prellenar las tiendas seleccionadas
        $data['stores'] = $this->record->stores()->pluck('stores.id')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validaciones de unicidad

        // Verificar unicidad del correo electrónico, ignorando el actual
        if (User::where('email', $data['email'])->where('id', '!=', $this->record->id)->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El correo electrónico ya está registrado.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'email' => 'El correo electrónico ya está registrado.',
            ]);
        }

        // Verificar unicidad del número de teléfono, ignorando el actual
        if (!empty($data['phone_number']) && User::where('phone_number', $data['phone_number'])->where('id', '!=', $this->record->id)->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El número de teléfono ya está registrado.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'phone_number' => 'El número de teléfono ya está registrado.',
            ]);
        }

        // Verificar unicidad del documento de identidad, ignorando el actual
        $data['identity_document'] = $data['identity_prefix'] . '-' . $data['identity_number'];

        if (User::where('identity_document', $data['identity_document'])->where('id', '!=', $this->record->id)->exists()) {
            Notification::make()
                ->title('Error')
                ->body('El documento de identidad ya está registrado.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'identity_document' => 'El documento de identidad ya está registrado.',
            ]);
        }

        // Eliminar los campos separados para que no causen errores al guardar
        unset($data['identity_prefix'], $data['identity_number']);

        return $data;
    }
}