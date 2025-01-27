<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\CustomersResource\Pages;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static ?string $tenantOwnershipRelationshipName = 'customerStores';

    protected static ?string $navigationGroup = 'Usuarios';

    protected static ?string $modelLabel = 'Editar Clientes';

    // Desactivar la página de creación
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery()) // Filtrar clientes por tienda
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellido')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número de Teléfono')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de Nacimiento')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    /**
     * Filtrar clientes por la tienda en sesión.
     */
    public static function getTableQuery(): Builder
    {
        $currentStore = Filament::getTenant();

        if (! $currentStore) {
            // Si no hay tienda en sesión, devuelve una consulta vacía
            return User::query()->whereRaw('1 = 0');
        }

        // Filtra los clientes asociados a la tienda actual
        return User::query()->whereHas('customerStores', function (Builder $query) use ($currentStore) {
            $query->where('store_id', $currentStore->id);
        });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'edit' => Pages\EditCustomers::route('/{record}/edit'),
        ];
    }
}
