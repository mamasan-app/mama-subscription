<?php

declare(strict_types=1);

namespace App\Filament\Store\Pages;

use App\Filament\Store\Fields\StoreFileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditStoreProfile extends EditTenantProfile
{
    // URL slug para acceder a la página de edición
    protected static ?string $slug = 'tienda/editar';

    public static function getLabel(): string
    {
        return 'Perfil de la tienda';
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(2) // Definir dos columnas para el formulario
            ->schema([
                // Subida del logo de la tienda
                StoreFileUpload::make('logo')
                    ->columnSpanFull()  // Ocupar todo el ancho
                    ->avatar(),  // Mostrar como un avatar (imagen circular)

                // Nombre de la tienda
                TextInput::make('name')
                    ->label('Nombre de la tienda')
                    ->required(),  // Campo requerido

                // Slug de la tienda (link)
                TextInput::make('slug')
                    ->label('Link')
                    ->disabled()  // El slug no se puede cambiar
                    ->prefix('https://')  // Prefijo para el enlace
                    ->suffix('.'.config('app.domain')),  // Sufijo con el dominio de la app

                // Descripción de la tienda
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),

                // Repeater para gestionar direcciones
                Repeater::make('addresses')
                    ->relationship('addresses') // Relacionado con el modelo Store
                    ->label('Direcciones de la tienda')
                    ->schema([
                        TextInput::make('branch')
                            ->label('Sucursal')
                            ->columnSpanFull()
                            ->required(),
                        TextInput::make('location')
                            ->label('Dirección')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->createItemButtonLabel('Agregar nueva dirección'),

                // Verificación del estatus de la tienda (booleano)
                Toggle::make('verified')  // Usa Toggle en lugar de TextInput
                    ->label('Estado de Verificación')
                    ->disabled()  // No se puede modificar manualmente
                    ->default(false),
            ]);
    }
}
