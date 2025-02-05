<?php

namespace App\Filament\Store\Resources\BankAccountResource\Pages;

use App\Filament\Store\Resources\BankAccountResource;
use App\Models\BankAccount;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtener la tienda en sesión
        $currentStore = Filament::getTenant();

        if ($currentStore) {
            // Asignar el store_id a la cuenta bancaria
            $data['store_id'] = $currentStore->id;

            // Asignar el user_id al propietario de la tienda
            $data['user_id'] = $currentStore->owner_id;

            // Verificar si se está marcando esta cuenta como predeterminada
            if (!empty($data['default_account']) && $data['default_account'] == true) {
                // Desmarcar cualquier otra cuenta predeterminada asociada a la misma tienda
                BankAccount::where('store_id', $currentStore->id)
                    ->update(['default_account' => false]);
            }
        }

        return $data;
    }
}
