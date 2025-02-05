<?php

namespace App\Filament\Store\Resources\BankAccountResource\Pages;

use App\Filament\Store\Resources\BankAccountResource;
use App\Models\BankAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Verificar si se está marcando esta cuenta como predeterminada
        if (!empty($data['default_account']) && $data['default_account'] == true) {
            // Desmarcar cualquier otra cuenta predeterminada asociada a la misma tienda
            BankAccount::where('store_id', $data['store_id'])
                ->where('id', '!=', $this->record->id ?? null)
                ->update(['default_account' => false]);
        }

        return $data;
    }
}
