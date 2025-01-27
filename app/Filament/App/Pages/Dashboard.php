<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\PaymentCalendarWidget;
use App\Filament\App\Widgets\PaymentHistoryWidget;
use App\Filament\App\Widgets\PaymentStatsWidget;
use Filament\Pages\Dashboard as FilamentDashboard;

class Dashboard extends FilamentDashboard
{
    public function getWidgets(): array
    {
        return [
            PaymentStatsWidget::make(),
            PaymentHistoryWidget::make(),
            PaymentCalendarWidget::make(), // El widget del calendario de pagos
        ];
    }
}
