<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Payment;

class PaymentSubscriptionsWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Pagos';
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

                Tables\Columns\TextColumn::make('subscription.id')
                    ->label('ID Suscripción')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => ucfirst($state->value)),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto')
                    ->formatStateUsing(fn($amount) => $amount ? '$' . number_format($amount / 100, 2) : 'N/A'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fecha de Vencimiento')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Puedes añadir filtros aquí si es necesario
            ])
            ->actions([
                // Define acciones aquí si es necesario
            ]);
    }

    protected function getQuery()
    {
        // Recupera todos los pagos relacionados con la suscripción actual
        return Payment::query()
            ->where('subscription_id', $this->record->id);
    }
}
