<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Stripe\Stripe;
use Stripe\Balance;

class StripeBalanceStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Configura la API de Stripe
        Stripe::setApiKey(config('stripe.secret_key'));

        try {
            // Obtén el balance desde Stripe
            $balance = Balance::retrieve();

            // Obtén el balance disponible y pendiente en formato adecuado
            $availableBalance = collect($balance->available)
                ->where('currency', 'usd')
                ->sum('amount') / 100; // Convertir de centavos a dólares

            $pendingBalance = collect($balance->pending)
                ->where('currency', 'usd')
                ->sum('amount') / 100; // Convertir de centavos a dólares
        } catch (\Exception $e) {
            // Manejo de errores
            $availableBalance = 0;
            $pendingBalance = 0;
        }

        return [
            Stat::make('Balance Disponible en Stripe', "$" . number_format($availableBalance, 2)),
            Stat::make('Balance Pendiente en Stripe', "$" . number_format($pendingBalance, 2)),
        ];
    }
}
