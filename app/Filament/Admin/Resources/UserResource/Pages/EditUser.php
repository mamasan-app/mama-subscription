<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si se está actualizando la contraseña, hashearla
        if (! empty($data['new_password'])) {
            $data['password'] = bcrypt($data['new_password']);
        }

        // Guarda el rol en una propiedad temporal para usarlo después de guardar
        $this->role = $data['role'];

        // Elimina los campos 'role', 'new_password' y 'new_password_confirmation' para que no intenten guardarse directamente en la base de datos
        unset($data['role']);
        unset($data['new_password']);
        unset($data['new_password_confirmation']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Asignar el rol basado en la selección del formulario
        $this->record->syncRoles($this->role);
    }
}
