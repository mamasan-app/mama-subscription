<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Suscripciones';

    /**
     * Define el formulario para crear/editar suscripciones.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Selector de Cliente
                Forms\Components\Select::make('user_id')
                    ->label('Cliente')
                    ->options(function () {
                        $currentStore = Filament::getTenant();

                        if ($currentStore) {
                            // Filtrar usuarios que sean clientes de la tienda en sesión
                            return User::whereHas('stores', function (Builder $query) use ($currentStore) {
                                $query->where('store_id', $currentStore->id)
                                    ->where('store_user.role', 'customer'); // Rol definido como 'customer'
                            })
                                ->get()
                                ->pluck('name', 'id'); // Accesor para obtener el nombre completo
                        }

                        return [];
                    })
                    ->searchable()
                    ->required(),

                // Selector de Plan
                Forms\Components\Select::make('service_id') // Asociado al modelo 'Plan'
                    ->label('Plan')
                    ->options(function () {
                        $currentStore = Filament::getTenant();

                        if ($currentStore) {
                            // Filtrar planes por tienda actual y solo planes publicados
                            return Plan::where('store_id', $currentStore->id)
                                ->where('published', true) // Solo planes publicados
                                ->pluck('name', 'id');
                        }

                        return [];
                    })
                    ->searchable()
                    ->required(),
            ]);
    }

    /**
     * Define la tabla para listar suscripciones.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery()) // Filtrar suscripciones por tienda actual
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Plan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state->value))),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Fin del Período de Prueba')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Fecha de Expiración')
                    ->dateTime()
                    ->sortable(),
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
     * Filtra las suscripciones para que solo se muestren las relacionadas a la tienda actual.
     */
    public static function getTableQuery(): Builder
    {
        $currentStore = Filament::getTenant();

        if (!$currentStore) {
            // Si no hay tienda en sesión, no mostrar resultados
            return Subscription::query()->whereRaw('1 = 0');
        }

        // Filtrar suscripciones asociadas a los planes de la tienda actual
        return Subscription::query()->whereHas('service', function (Builder $query) use ($currentStore) {
            $query->where('store_id', $currentStore->id)
                ->where('published', true); // Solo planes publicados
        });
    }

    /**
     * Relación con las páginas de Filament.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
