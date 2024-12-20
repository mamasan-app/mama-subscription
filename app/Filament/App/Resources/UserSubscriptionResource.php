<?php
namespace App\Filament\App\Resources;

use App\Models\Subscription;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Filament\App\Resources\UserSubscriptionResource\Pages;
use App\Enums\SubscriptionStatusEnum;
use Filament\Forms\Form;

class UserSubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('user.name')
                ->label('Cliente')
                ->disabled(), // Desactiva el campo para solo lectura

            Forms\Components\TextInput::make('service.name')
                ->label('Servicio')
                ->disabled(), // Desactiva el campo

            Forms\Components\TextInput::make('status')
                ->label('Estado')
                ->disabled(), // Desactiva el campo

            Forms\Components\DateTimePicker::make('trial_ends_at')
                ->label('Fin del Período de Prueba')
                ->disabled(), // Desactiva el campo

            Forms\Components\DateTimePicker::make('expires_at')
                ->label('Fecha de Expiración')
                ->disabled(), // Desactiva el campo

            Forms\Components\TextInput::make('formattedPrice')
                ->label('Precio')
                ->disabled(), // Desactiva el campo
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery()) // Llama al método de la consulta personalizada
            ->defaultSort('created_at', 'desc') // Ordenar por defecto por fecha de creación, descendente
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('service.name')->label('Servicio')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state?->getLabel()),
                Tables\Columns\TextColumn::make('trial_ends_at')->label('Fin del Período de Prueba')->dateTime(),
                Tables\Columns\TextColumn::make('expires_at')->label('Fecha de Expiración')->dateTime(),
            ])
            ->actions([
                Action::make('Pagar')
                    ->url(fn(Subscription $record): string => Pages\UserSubscriptionPayment::getUrl(['record' => $record]))
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Pagar')
                    ->button()
                    ->visible(fn(Subscription $record) => $record->transactions()->count() === 0), // Mostrar solo si no hay transacciones
            ]);

    }



    public static function getTableQuery()
    {
        // Obtén al usuario actualmente autenticado
        $currentUser = auth()->user();

        // Filtra las suscripciones asociadas al usuario autenticado
        return Subscription::query()->where('user_id', $currentUser->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserSubscriptions::route('/'),
            'create' => Pages\CreateUserSubscription::route('/create'),
            'edit' => Pages\EditUserSubscription::route('/{record}/edit'),
            'payment' => Pages\UserSubscriptionPayment::route('/{record}/payment'),
        ];
    }
}
