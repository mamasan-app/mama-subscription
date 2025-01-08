<?php

namespace App\Filament\App\Resources\BankAccountResource\Pages;

use App\Filament\App\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user(); // Obtener el usuario autenticado

        // Asignar el ID del usuario autenticado
        $data['user_id'] = $user->id;

        // Obtener la cédula del usuario y remover el guion
        $data['identity_number'] = str_replace('-', '', $user->identity_document);

        // Concatenar el prefijo telefónico y el número telefónico
        $data['phone_number'] = $data['phone_prefix'] . $data['phone_number'];

        // Eliminar el campo `phone_prefix` ya que no es necesario almacenarlo por separado
        unset($data['phone_prefix']);

        return $data;
    }
}
