<?php

namespace App\Filament\App\Resources\BankAccountResource\Pages;

use App\Filament\App\Resources\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\BankAccount;

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

        // Verificar si el usuario ya tiene una cuenta predeterminada
        $hasDefaultAccount = BankAccount::where('user_id', $user->id)
            ->where('default_account', true)
            ->exists();

        // Si no tiene una cuenta predeterminada, marcar esta como predeterminada
        $data['default_account'] = !$hasDefaultAccount;

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user(); // Obtener el usuario autenticado

        // Asegurar que solo una cuenta sea predeterminada
        $createdAccount = $this->record; // Obtener la cuenta recién creada

        if ($createdAccount->default_account) {
            // Desmarcar otras cuentas como predeterminadas
            BankAccount::where('user_id', $user->id)
                ->where('id', '!=', $createdAccount->id)
                ->update(['default_account' => false]);
        }
    }
}
