<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PaymentResource\Pages;
use App\Filament\App\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Gestión de Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subscription.service_name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->getStateUsing(fn($record) => number_format($record->amount_cents / 100, 2) . ' USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Completado' => 'success',
                        'Pendiente' => 'warning',
                        'Fallido', 'Cancelado' => 'danger',
                        'Incobrable' => 'secondary',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('No disponible'),
            ])
            ->filters([
                Tables\Filters\Filter::make('Estado: Completado')
                    ->query(fn($query) => $query->where('status', PaymentStatusEnum::Completed->value)),

                Tables\Filters\Filter::make('Estado: Pendiente')
                    ->query(fn($query) => $query->where('status', PaymentStatusEnum::Pending->value)),

                Tables\Filters\Filter::make('Vencidos')
                    ->query(fn($query) => $query->where('due_date', '<', now())->whereNull('paid_date')),
            ])
            ->actions([

            ])
            ->bulkActions([

            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles del Pago')
                    ->tabs([
                        // Pestaña Información del Pago
                        Tab::make('Pago')
                            ->schema([
                                TextEntry::make('stripe_invoice_id')
                                    ->label('ID de Factura (Stripe)')
                                    ->placeholder('No disponible'),
                                TextEntry::make('amount_cents')
                                    ->label('Monto')
                                    ->getStateUsing(fn($record) => number_format($record->amount_cents / 100, 2) . ' USD')
                                    ->placeholder('No disponible'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn($record) => $record->status->getColor())
                                    ->placeholder('No disponible'),
                                TextEntry::make('paid_date')
                                    ->label('Fecha de Pago')
                                    ->dateTime('d/m/Y')
                                    ->placeholder('No disponible'),
                            ])->columns(2),

                        // Pestaña Información de la Suscripción
                        Tab::make('Suscripción')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->getStateUsing(fn($record) => $record->status->getLabel())
                                    ->badge()
                                    ->color(fn($record) => $record->status->getColor()),
                                TextEntry::make('trial_ends_at')
                                    ->label('Fin del Periodo de Prueba')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('renews_at')
                                    ->label('Renovación')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('ends_at')
                                    ->label('Fecha de Finalización')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('last_notification_at')
                                    ->label('Última Notificación')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('expires_at')
                                    ->label('Fecha de Expiración')
                                    ->dateTime()
                                    ->placeholder('No disponible'),
                                TextEntry::make('frequency_days')
                                    ->label('Frecuencia de Pago (días)')
                                    ->placeholder('No disponible'),
                            ])->columns(2),
                        // Pestaña Información del Plan
                        Tab::make('Plan')
                            ->schema([
                                TextEntry::make('service_name')
                                    ->label('Nombre del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('service_description')
                                    ->label('Descripción del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('formattedServicePrice')
                                    ->label('Precio del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('service_free_days')
                                    ->label('Días Gratis')
                                    ->placeholder('No disponible'),
                                TextEntry::make('service_grace_period')
                                    ->label('Período de Gracia')
                                    ->placeholder('No disponible'),
                            ])->columns(2),
                    ])->columnSpanFull(),
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
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
