<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\PaymentCalendarWidget;
use App\Filament\App\Widgets\PaymentStatsWidget;
use App\Filament\App\Widgets\PaymentHistoryWidget;
use App\Filament\App\Widgets\NextPaymentWidget;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Enums\SubscriptionStatusEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as FilamentDashboard;

class Dashboard extends FilamentDashboard
{

    public function getWidgets(): array
    {
        return [
            PaymentStatsWidget::class,
            NextPaymentWidget::class,
            PaymentHistoryWidget::class,
            PaymentCalendarWidget::make(), // El widget del calendario de pagos
        ];
    }
}
