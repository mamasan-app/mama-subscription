<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PaymentResource\Pages;
use App\Filament\App\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
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
                //
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles del Pago')
                    ->tabs([
                        // Pestaña Información del Pago
                        Tab::make('Pago')
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('stripe_invoice_id')
                                            ->label('ID de Factura (Stripe)')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('amount_cents')
                                            ->label('Monto (cents)')
                                            ->getStateUsing(fn($record) => number_format($record->amount_cents / 100, 2) . ' USD'),
                                        TextEntry::make('status')
                                            ->label('Estado')
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'Completed' => 'success',
                                                'Pending' => 'warning',
                                                'Cancelled' => 'danger',
                                                default => 'secondary',
                                            }),
                                        TextEntry::make('due_date')
                                            ->label('Fecha de Vencimiento')
                                            ->dateTime('d/m/Y'),
                                        TextEntry::make('paid_date')
                                            ->label('Fecha de Pago')
                                            ->dateTime('d/m/Y'),
                                    ]),
                            ]),
                        // Pestaña Información de la Suscripción
                        Tab::make('Suscripción')
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('subscription.service_name')
                                            ->label('Nombre del Servicio')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('subscription.renews_at')
                                            ->label('Renovación')
                                            ->dateTime('d/m/Y')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('subscription.status')
                                            ->label('Estado de la Suscripción')
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'Active' => 'success',
                                                'OnTrial' => 'info',
                                                'Cancelled' => 'danger',
                                                default => 'secondary',
                                            }),
                                    ]),
                            ]),
                        // Pestaña Información del Plan
                        Tab::make('Plan')
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('subscription.service.name')
                                            ->label('Nombre del Plan')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('subscription.service.price_cents')
                                            ->label('Precio del Plan (cents)')
                                            ->getStateUsing(fn($record) => number_format($record->subscription->service->price_cents / 100, 2) . ' USD'),
                                        TextEntry::make('subscription.service.duration')
                                            ->label('Duración (días)')
                                            ->placeholder('No disponible'),
                                    ]),
                            ]),
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
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
