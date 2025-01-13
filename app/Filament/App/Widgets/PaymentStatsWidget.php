<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Payment;
use App\Models\Subscription;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Obtener el total de dinero pagado hasta ahora (completado)
        $totalPaidCents = Payment::where('status', 'completed')->sum('amount_cents');
        $totalPaidDollars = $totalPaidCents / 100;

        // Obtener el total a pagar en los próximos 7 días
        $upcomingWeekTotalCents = Subscription::whereBetween('renews_at', [now(), now()->addDays(7)])
            ->sum('service_price_cents');
        $upcomingWeekTotalDollars = $upcomingWeekTotalCents / 100;

        // Obtener el total de pagos pendientes o fallidos
        $failedOrPendingCents = Payment::whereIn('status', ['failed', 'pending'])->sum('amount_cents');
        $failedOrPendingDollars = $failedOrPendingCents / 100;

        return [
            Stat::make('Total Pagado', '$' . number_format($totalPaidDollars, 2)),
            Stat::make('Pagos Próxima Semana', '$' . number_format($upcomingWeekTotalDollars, 2)),
            Stat::make('Pagos Pendientes/Fallidos', '$' . number_format($failedOrPendingDollars, 2)),
        ];
    }
}
