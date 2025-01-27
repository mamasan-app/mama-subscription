<?php

namespace App\Filament\Admin\Resources\StoreResource\Pages;

use App\Filament\Admin\Resources\StoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;

    protected function afterCreate(): void
    {
        // Asumimos que quieres asignar el rol de 'owner_store' al propietario (owner_id)
        $owner = \App\Models\User::find($this->record->owner_id);

        // Agregar la relación en la tabla intermedia store_user
        if ($owner) {
            $owner->stores()->attach($this->record->id, ['role' => 'owner_store']);
        }
    }
}
