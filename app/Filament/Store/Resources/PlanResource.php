<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\PlanResource\Pages;
use App\Models\Plan;
use App\Models\Frequency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;


class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $modelLabel = 'Planes';
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
                    ->label('Sucursales')
                    ->options(function () {
                        $currentStore = Filament::getTenant();
                        if ($currentStore) {
                            return $currentStore->addresses()->pluck('branch', 'id');
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
                        return Frequency::pluck('name', 'id');
                    })
                    ->preload(),

                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->columnSpanFull()
                    ->default(0),

                Forms\Components\TextInput::make('free_days')
                    ->label('Días Gratuitos')
                    ->required()
                    ->numeric()
                    ->inputMode('numeric')
                    ->minValue(1)
                    ->maxValue(365)
                    ->placeholder('Ingresa el número de días gratuitos'),

                Forms\Components\TextInput::make('grace_period')
                    ->label('Período de Gracia')
                    ->required()
                    ->numeric()
                    ->inputMode('numeric')
                    ->minValue(1)
                    ->maxValue(365)
                    ->placeholder('Días adicionales para cancelar después del vencimiento del período de prueba'),

                Forms\Components\Toggle::make('infinite_duration')
                    ->label('Duración Infinita')
                    ->default(true)  // Establecer como seleccionado por defecto
                    ->required()
                    ->reactive(), // Permitir que reaccione a cambios y modifique otros campos dinámicamente

                Forms\Components\TextInput::make('duration')
                    ->label('Duración (días)')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('Ingresa la duración en días')
                    ->required(fn($get) => !$get('infinite_duration')) // Requerido solo si la duración no es infinita
                    ->hidden(fn($get) => $get('infinite_duration')), // Ocultar si es duración infinita


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
                Tables\Columns\TextColumn::make('frequency.name')  // Usamos la relación con la tabla de frecuencias
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

                Tables\Columns\IconColumn::make('infinite_duration')
                    ->label('Duración Infinita')
                    ->boolean(),

                Tables\Columns\TextColumn::make('duration_text')
                    ->label('Duración')
                    ->sortable(),



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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
