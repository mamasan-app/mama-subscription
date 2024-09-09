<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\ServiceResource\Pages;
use App\Filament\Store\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\Select::make('store_id')
                    ->label('Tienda')
                    ->options(function () {
                        return auth()->user()->stores()->pluck('stores.name', 'stores.id');
                    })
                    ->required()
                    ->preload(),
                Forms\Components\Select::make('variant')
                    ->label('Frecuencia')
                    ->required()
                    ->options([
                        'mensual' => 'Mensual',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ])
                    ->default('Mensual'),
                Forms\Components\TextInput::make('price_cents')
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
            ->query(static::getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('variant')
                    ->label('Frecuencia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Precio')
                    ->numeric()
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

    public static function getTableQuery()
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Obtener todos los IDs de las tiendas asociadas al usuario autenticado
        $storeIds = $authUser->stores()->pluck('stores.id')->toArray();

        return Service::query()
            ->whereHas('store', function ($query) use ($storeIds) {
                $query->whereIn('id', $storeIds);
            });
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
