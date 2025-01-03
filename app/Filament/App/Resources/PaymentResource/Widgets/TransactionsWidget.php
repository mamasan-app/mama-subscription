<?php

namespace App\Filament\App\Resources\PaymentResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Transaction;

class TransactionsWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Transacciones relacionadas';
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Transacción')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Completado' => 'success',
                        'Pendiente' => 'warning',
                        'Fallido', 'Cancelado' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn($record) => '$' . number_format($record->amount / 100, 2)),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([]);
    }

    protected function getQuery()
    {
        // Recupera todas las transacciones relacionadas con el pago actual
        return Transaction::query()
            ->where('payment_id', $this->record->id);
    }
}
