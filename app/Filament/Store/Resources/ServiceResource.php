<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Models\Frequency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;


class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $modelLabel = 'Servicios';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),

                Forms\Components\Select::make('address_id')
                    ->label('Dirección')
                    ->options(function () {
                        $currentStore = Filament::getTenant();
                        if ($currentStore) {
                            return $currentStore->addresses()->pluck('short_address', 'id');
                        }
                        return [];
                    })
                    ->multiple()
                    ->required()
                    ->preload(),

                Forms\Components\Select::make('frequency_id')
                    ->label('Frecuencia')
                    ->required()
                    ->options(function () {
                        // Obtener las frecuencias de la base de datos
                        return Frequency::pluck('nombre', 'id');
                    })
                    ->preload(),

                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->columnSpanFull()
                    ->default(0),

                Forms\Components\Toggle::make('published')
                    ->label('Publicado')
                    ->required(),
                Forms\Components\Toggle::make('featured')
                    ->label('Destacado')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('frequency.nombre')  // Usamos la relación con la tabla de frecuencias
                    ->label('Frecuencia')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('formattedPrice')
                    ->label('Precio')
                    ->sortable(),
                Tables\Columns\IconColumn::make('published')
                    ->label('Publicado')
                    ->boolean(),
                Tables\Columns\IconColumn::make('featured')
                    ->label('Destacado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Edición')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Fecha de Eliminación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
