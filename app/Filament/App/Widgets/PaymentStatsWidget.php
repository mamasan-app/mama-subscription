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

        // Obtener el total de pagos pendientes (estado 'pending') y suscripciones en periodo de prueba
        $pendingPaymentsCents = Payment::where('status', 'pending')->sum('amount_cents');
        $trialSubscriptionsCents = Subscription::where('status', 'on_trial')->sum('service_price_cents');
        $totalPendingCents = $pendingPaymentsCents + $trialSubscriptionsCents;
        $totalPendingDollars = $totalPendingCents / 100;

        return [
            Stat::make('Total Pagado', '$' . number_format($totalPaidDollars, 2)),
            Stat::make('Pagos Próxima Semana', '$' . number_format($upcomingWeekTotalDollars, 2)),
            Stat::make('Pendientes y Pruebas', '$' . number_format($totalPendingDollars, 2)),
        ];
    }
}
