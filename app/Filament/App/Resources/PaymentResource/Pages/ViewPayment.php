<?php

namespace App\Filament\App\Resources\PaymentResource\Pages;

use App\Filament\App\Resources\PaymentResource;
use App\Filament\App\Resources\PaymentResource\Widgets\TransactionsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    /**
     * Muestra el widget de transacciones en el pie de la página.
     */
    protected function getFooterWidgets(): array
    {
        return [
            TransactionsWidget::class,
        ];
    }
}
