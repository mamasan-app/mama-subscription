<?php

namespace App\Filament\Store\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Facades\Filament;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Obtener el store_id del tenant actual
        $currentStore = Filament::getTenant();

        if (!$currentStore) {
            return [
                Stat::make('Total Pagado', '$0.00'),
                Stat::make('Pagos Próxima Semana', '$0.00'),
                Stat::make('Pendientes y Pruebas', '$0.00'),
            ];
        }

        // Total de dinero pagado hasta ahora (estado 'completed')
        $totalPaidCents = Payment::whereHas('subscription', function ($query) use ($currentStore) {
            $query->where('store_id', $currentStore->id);
        })->where('status', 'completed')->sum('amount_cents');
        $totalPaidDollars = $totalPaidCents / 100;

        // Total a pagar en los próximos 7 días (renovaciones y periodo de prueba)
        $upcomingWeekTotalCents = Subscription::where('store_id', $currentStore->id)
            ->whereBetween('renews_at', [now(), now()->addDays(7)])
            ->whereColumn('renews_at', '!=', 'ends_at') // Excluir `renews_at == ends_at`
            ->where(function ($query) {
                $query->where('status', 'active') // Solo incluir suscripciones activas
                    ->orWhere('status', 'on_trial'); // O en periodo de prueba
            })
            ->sum('service_price_cents');
        $upcomingWeekTotalDollars = $upcomingWeekTotalCents / 100;


        // Total de pagos pendientes (estado 'pending') + periodo de prueba
        $pendingPaymentsCents = Payment::whereHas('subscription', function ($query) use ($currentStore) {
            $query->where('store_id', $currentStore->id);
        })->where('status', 'pending')->sum('amount_cents');

        $trialSubscriptionsCents = Subscription::where('store_id', $currentStore->id)
            ->where('status', 'on_trial')->sum('service_price_cents');

        $totalPendingCents = $pendingPaymentsCents + $trialSubscriptionsCents;
        $totalPendingDollars = $totalPendingCents / 100;

        return [
            Stat::make('Total Pagado', '$' . number_format($totalPaidDollars, 2)),
            Stat::make('Pagos Proximos 7 Días', '$' . number_format($upcomingWeekTotalDollars, 2)),
            Stat::make('Pendientes y Pruebas', '$' . number_format($totalPendingDollars, 2)),
        ];
    }
}
