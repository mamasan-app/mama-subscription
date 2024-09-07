<?php

namespace App\Filament\Store\Resources\EmpleadosResource\Pages;

use App\Filament\Store\Resources\EmpleadosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmpleados extends EditRecord
{
    protected static string $resource = EmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
