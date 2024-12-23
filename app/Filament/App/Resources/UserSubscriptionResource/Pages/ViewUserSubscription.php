<?php

namespace App\Filament\App\Resources\UserSubscriptionResource\Pages;

use App\Filament\App\Resources\UserSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUserSubscription extends ViewRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    public function getTitle(): string
    {
        return 'Ver Suscripcion';
    }
}
