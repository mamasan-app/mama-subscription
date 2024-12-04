<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\SubscriptionResource\Pages;
use App\Filament\Store\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                            // Obtén los usuarios asociados a la tienda como "customers"
                            return User::whereHas('stores', function (Builder $query) use ($currentStore) {
                                $query->where('store_id', $currentStore->id)
                                    ->where('store_user.role', 'customer'); // Cambia 'pivot' por 'store_user.role'
                            })
                                // Obtén todos los usuarios y luego crea una colección con sus nombres completos
                                ->get()
                                ->pluck('name', 'id');  // Utiliza el accessor 'name'
                        }

                        return [];
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('service_id') // Cambiar 'plan_id' por 'service_id'
                    ->label('Plan')
                    ->options(function () {
                        $currentStore = Filament::getTenant();
                        if ($currentStore) {
                            return Plan::where('store_id', $currentStore->id)
                                ->pluck('name', 'id');
                        }
                        return [];
                    })
                    ->searchable()
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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

                Tables\Columns\TextColumn::make('renews_at')
                    ->label('Fecha de Renovación')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Fecha de Expiracion')
                    ->dateTime()
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
