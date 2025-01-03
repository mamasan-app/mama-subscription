<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Transacción')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($record) => $record->status->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn($record) => '$' . number_format($record->amount / 100, 2)),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
                Tabs::make('Detalles de la Transaccion')
                    ->tabs([
                        // Pestaña Información de la Transaccion
                        Tab::make('Informacion General')
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID de Transacción'),
                                TextEntry::make('type')
                                    ->label('Tipo de Transacción')
                                    ->getStateUsing(fn($record) => $record->type->getLabel()),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn($record) => $record->status->getColor()),
                                TextEntry::make('date')
                                    ->label('Fecha de Transacción')
                                    ->dateTime('d/m/Y'),
                                TextEntry::make('amount_cents')
                                    ->label('Monto (USD)')
                                    ->getStateUsing(fn($record) => number_format($record->amount_cents, 2) . ' USD'),
                                TextEntry::make('from_user_name')
                                    ->label('Usuario (From)')
                                    ->getStateUsing(fn($record) => $record->from->name ?? 'No disponible'),
                                TextEntry::make('to_name')
                                    ->label('Destino (To)')
                                    ->getStateUsing(function ($record) {
                                        if ($record->to_type === 'App\\Models\\Store') {
                                            return $record->to->name ?? 'No disponible';
                                        }
                                        if ($record->to_type === 'App\\Models\\User') {
                                            return $record->to->name ?? 'No disponible';
                                        }
                                        return 'No disponible';
                                    }),
                            ])->columns(2),

                        // Pestaña Información Detallada
                        Tab::make('Información Detallada')
                            ->schema([
                                TextEntry::make('metadata_details')
                                    ->label('Detalles de Metadata')
                                    ->getStateUsing(function ($record) {
                                        $metadata = $record->getMetadataAsObject();
                                        if ($metadata instanceof \App\DTO\StripeMetadata) {
                                            return "Stripe ID: {$metadata->id}, 
                                            Monto: {$metadata->amount} {$metadata->currency}, 
                                            Estado: {$metadata->status}, 
                                            Creado: {$metadata->created->format('d/m/Y H:i:s')}";
                                        } elseif ($metadata instanceof \App\DTO\MiBancoMetadata) {
                                            return "Código: {$metadata->code}, 
                                            Mensaje: {$metadata->message}, 
                                            Referencia: {$metadata->reference}, 
                                            ID: {$metadata->id}";
                                        }
                                        return 'No disponible';
                                    })
                                    ->placeholder('No disponible'),
                            ])->columns(1),

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
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
