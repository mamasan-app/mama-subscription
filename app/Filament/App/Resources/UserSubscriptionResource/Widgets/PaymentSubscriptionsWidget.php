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

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn($record) => '$' . number_format($record->getAmountInDollarsAttribute(), 2)),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No disponible'),
            ])
            ->filters([

            ])
            ->actions([

            ]);
    }

    protected function getQuery()
    {
        // Recupera todos los pagos relacionados con la suscripción actual
        return Payment::query()
            ->where('subscription_id', $this->record->id);
    }
}
