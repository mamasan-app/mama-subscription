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
}
