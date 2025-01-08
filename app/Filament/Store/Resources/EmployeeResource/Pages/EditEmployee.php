<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

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
        // Combinar el prefijo y el número de la cédula al guardar
        $data['identity_document'] = $data['identity_prefix'] . '-' . $data['identity_number'];

        // Eliminar los campos separados para que no causen errores al guardar
        unset($data['identity_prefix'], $data['identity_number']);

        return $data;
    }
}

