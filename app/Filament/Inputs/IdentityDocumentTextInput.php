<?php

namespace App\Filament\Inputs;

use Filament\Forms\Components\TextInput;

class IdentityDocumentTextInput
{
    public static function make(string $name = 'identity_document'): TextInput
    {
        return TextInput::make($name)
            ->label('Documento de identidad')
            ->hint('Ej: V-12345678')
            ->placeholder('V-12345678')
            ->regex('/^[V|E|J|G]-\d{6,9}$/');
    }
}