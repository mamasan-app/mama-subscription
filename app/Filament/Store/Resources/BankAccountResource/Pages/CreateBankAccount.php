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
        $currentStore = Filament::getTenant();

        if ($currentStore) {
            $data['store_id'] = $currentStore->id;
            $data['user_id'] = $currentStore->owner_id;

            // Combinar el prefijo y el número de identidad
            $data['identity_number'] = $data['identity_prefix'].'-'.$data['identity_number'];
            $data['phone_number'] = $data['phone_prefix'].$data['phone_number'];

            if (! empty($data['default_account']) && $data['default_account'] == true) {
                BankAccount::where('store_id', $currentStore->id)
                    ->update(['default_account' => false]);
            }
        }

        return $data;
    }
}
