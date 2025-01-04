<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Pages;

use App\Filament\App\Resources\UserSubscriptionResource;
use App\Filament\App\Resources\UserSubscriptionResource\Widgets\PaymentSubscriptionsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUserSubscription extends ViewRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    /**
     * Muestra el widget de pagos en el pie de la página.
     */
    protected function getFooterWidgets(): array
    {
        return [
            PaymentSubscriptionsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Ver Suscripcion';
    }

    /**
     * Define las acciones adicionales para la página.
     */
    protected function getActions(): array
    {
        return [
            Actions\Action::make('Pagar')
                ->url(
                    fn($record): string => $record->payments->flatMap->transactions->isEmpty()
                    ? \App\Filament\App\Resources\UserSubscriptionResource\Pages\UserSubscriptionPayment::getUrl(['record' => $record])
                    : '/'
                )
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ->label('Pagar')
                ->button()
                ->visible(fn($record) => $record->stripe_subscription_id === null),
        ];
    }
}
