<?php
namespace App\Filament\App\Resources;

use App\Models\Subscription;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Filament\App\Resources\UserSubscriptionResource\Pages;
use Filament\Forms\Form;


class UserSubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;


    // Cambia los íconos y la navegación si lo necesitas
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([/* tus campos aquí */]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Cliente')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('service.name')->label('Servicio')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Estado')->sortable(),
            Tables\Columns\TextColumn::make('trial_ends_at')->label('Fin del Período de Prueba')->dateTime(),
            Tables\Columns\TextColumn::make('expires_at')->label('Fecha de Expiración')->dateTime(),
        ])
            ->actions([
                Action::make('Pagar')
                    ->url(fn(Subscription $record): string => Pages\UserSubscriptionPayment::getUrl(['record' => $record]))
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Pagar')
                    ->button(),
            ]);
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