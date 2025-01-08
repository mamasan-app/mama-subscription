<?php

namespace App\Filament\Inputs;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Enums\IdentityPrefixEnum;

class IdentityDocumentTextInput
{
    public static function make(string $prefixName = 'identity_prefix', string $numberName = 'identity_number'): Grid
    {
        return Grid::make(2)
            ->schema([
                Select::make($prefixName)
                    ->label('Tipo de Cédula')
                    ->options(
                        collect(IdentityPrefixEnum::cases())
                            ->mapWithKeys(fn($prefix) => [$prefix->value => $prefix->getLabel()])
                            ->toArray()
                    )
                    ->required(),
                TextInput::make($numberName)
                    ->label('Número de Cédula')
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(20)
                    ->required(),
            ]);
    }
}