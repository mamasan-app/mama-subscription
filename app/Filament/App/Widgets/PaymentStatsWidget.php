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
        $upcomingWeekTotalCents = Subscription::where(function ($query) {
            $query->whereBetween('renews_at', [now(), now()->addDays(7)])
                ->whereColumn('renews_at', '!=', 'ends_at'); // Excluir `renews_at == ends_at`
        })
            ->orWhere('status', 'on_trial') // Incluir suscripciones en prueba
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
                    ->whereColumn('renews_at', '!=', 'ends_at'); // Excluir `renews_at == ends_at`
            })
            ->orWhere('status', 'on_trial') // Incluir suscripciones en prueba
            ->orderBy('renews_at')
            ->first();

        $nextPaymentStat = $nextPayment
            ? Stat::make('Próximo Pago', '$' . number_format($nextPayment->service_price_cents / 100, 2))
                ->description($nextPayment->renews_at->format('d/m/Y')) // Coloca la fecha debajo
            : Stat::make('Próximo Pago', 'No tiene pagos pendientes');

        return [
            Stat::make('Total Pagado', '$' . number_format($totalPaidDollars, 2)),
            Stat::make('Pagos Próximos 7 días', '$' . number_format($upcomingWeekTotalDollars, 2)),
            Stat::make('Pendientes y Pruebas', '$' . number_format($totalPendingDollars, 2)),
            $nextPaymentStat,
        ];
    }
}
