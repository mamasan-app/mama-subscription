<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;
use App\Models\Payment;
use Filament\Tables\Actions\Action;

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
                //Tables\Columns\TextColumn::make('id')
                //    ->label('ID')
                //    ->sortable(),
//
                //Tables\Columns\TextColumn::make('service_name')
                //    ->label('Plan')
                //    ->sortable()
                //    ->searchable(),
//
                //Tables\Columns\TextColumn::make('status')
                //    ->label('Estado')
                //    ->sortable()
                //    ->formatStateUsing(fn($state) => $state?->getLabel()),
//
                //Tables\Columns\TextColumn::make('created_at')
                //    ->label('Fecha de Creación')
                //    ->dateTime()
                //    ->sortable(),
//
                //Tables\Columns\TextColumn::make('updated_at')
                //    ->label('Última Actualización')
                //    ->dateTime()
                //    ->sortable(),
            ])
            ->filters([
                // Puedes añadir filtros aquí si es necesario
            ])
            ->actions([
                Action::make('Pagar')
                    ->url(fn(Subscription $record): string => \App\Filament\App\Resources\UserSubscriptionResource\Pages\UserSubscriptionPayment::getUrl(['record' => $record]))
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Pagar')
                    ->button()
                    ->visible(fn(Subscription $record) => $record->payments->flatMap->transactions->isEmpty()), // Mostrar solo si no hay transacciones
            ]);
    }

    protected function getQuery()
    {

        $subscriptionId = $this->record->id;

        $paymentSubscription = Payment::query()
            ->where('subscription_id', $subscriptionId)
            ->pluck('id');

        return Subscription::query()
            ->whereIn('id', $paymentSubscription);
    }
}
