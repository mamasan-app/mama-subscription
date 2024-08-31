<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hash the password before storing it
        $data['password'] = bcrypt($data['password']);
        
        // Guarda el rol en una propiedad temporal para usarlo en afterCreate
        $this->role = $data['role'];

        // Elimina el campo 'role' del array de datos para que no intente guardarse en la base de datos
        unset($data['role']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Asignar el rol basado en la selección del formulario
        $this->record->assignRole($this->role);
    }
}

