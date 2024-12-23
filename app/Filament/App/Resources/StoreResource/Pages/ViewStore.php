<?php

namespace App\Filament\App\Resources\StoreResource\Pages;

use App\Filament\App\Resources\StoreResource;
use App\Filament\App\Resources\StoreResource\Widgets\StoreSubscriptionsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStore extends ViewRecord
{
    protected static string $resource = StoreResource::class;

    /**
     * Muestra el widget de suscripciones en el pie de la página.
     */
    protected function getFooterWidgets(): array
    {
        return [
            StoreSubscriptionsWidget::class,
        ];
    }
}
