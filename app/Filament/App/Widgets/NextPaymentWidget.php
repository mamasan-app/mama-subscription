<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Subscription;

class NextPaymentWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $nextPayment = Subscription::where('user_id', auth()->id())
            ->where('renews_at', '>=', now())
            ->orderBy('renews_at')
            ->first();

        if (!$nextPayment) {
            return [Stat::make('Próximo Pago', 'No tiene pagos pendientes')];
        }

        $nextPaymentAmount = $nextPayment->service_price_cents / 100;
        $nextPaymentDate = $nextPayment->renews_at->format('d/m/Y');

        return [
            Stat::make('Próximo Pago', '$' . number_format($nextPaymentAmount, 2)),
            Stat::make('Fecha de Vencimiento', $nextPaymentDate),
        ];
    }
}
