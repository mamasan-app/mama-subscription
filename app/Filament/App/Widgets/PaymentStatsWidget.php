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
        // Total de dinero pagado hasta ahora (estado 'completed')
        $totalPaidCents = Payment::where('status', 'completed')->sum('amount_cents');
        $totalPaidDollars = $totalPaidCents / 100;

        // Total a pagar en los próximos 7 días (renovaciones y periodo de prueba)
        $upcomingWeekTotalCents = Subscription::whereBetween('renews_at', [now(), now()->addDays(7)])
            ->orWhere('status', 'on_trial') // Incluye las suscripciones en periodo de prueba
            ->sum('service_price_cents');
        $upcomingWeekTotalDollars = $upcomingWeekTotalCents / 100;

        // Total de pagos pendientes (estado 'pending') + periodo de prueba
        $pendingPaymentsCents = Payment::where('status', 'pending')->sum('amount_cents');
        $trialSubscriptionsCents = Subscription::where('status', 'on_trial')->sum('service_price_cents');
        $totalPendingCents = $pendingPaymentsCents + $trialSubscriptionsCents;
        $totalPendingDollars = $totalPendingCents / 100;

        // Próximo pago (considerando periodo de prueba como pendiente)
        $nextPayment = Subscription::where('user_id', auth()->id())
            ->where(function ($query) {
                $query->where('renews_at', '>=', now())
                    ->orWhere('status', 'on_trial'); // Incluye las de prueba
            })
            ->orderBy('renews_at')
            ->first();

        $nextPaymentStat = $nextPayment
            ? Stat::make('Próximo Pago', sprintf(
                '<span style="font-size: 1.25em; font-weight: bold;">$%s</span><br><span style="font-size: 0.75em; color: #6b7280;">%s</span>',
                number_format($nextPayment->service_price_cents / 100, 2),
                $nextPayment->renews_at->format('d/m/Y')
            ))->html() // Activa HTML en el contenido
            : Stat::make('Próximo Pago', 'No tiene pagos pendientes');

        return [
            Stat::make('Total Pagado', '$' . number_format($totalPaidDollars, 2)),
            Stat::make('Pagos Próxima Semana', '$' . number_format($upcomingWeekTotalDollars, 2)),
            Stat::make('Pendientes y Pruebas', '$' . number_format($totalPendingDollars, 2)),
            $nextPaymentStat,
        ];
    }
}
