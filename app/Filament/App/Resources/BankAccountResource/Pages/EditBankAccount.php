<?php

namespace App\Filament\App\Resources\BankAccountResource\Pages;

use App\Filament\App\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Enums\PhonePrefixEnum;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Extraer el prefijo y el número telefónico
        if (!empty($data['phone_number'])) {
            $prefix = substr($data['phone_number'], 0, 4); // Los primeros 4 dígitos
            $number = substr($data['phone_number'], 4); // Del quinto en adelante

            $data['phone_prefix'] = $prefix; // Asignar el prefijo
            $data['phone_number'] = $number; // Asignar solo el número
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Concatenar el prefijo y el número telefónico antes de guardar
        $data['phone_number'] = $data['phone_prefix'] . $data['phone_number'];

        // Eliminar el campo `phone_prefix` ya que no es necesario almacenarlo por separado
        unset($data['phone_prefix']);

        return $data;
    }
}
