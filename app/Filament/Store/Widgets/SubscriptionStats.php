<?php

declare(strict_types=1);

namespace App\Filament\Store\Widgets;

use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionStats extends BaseWidget
{
    public static function canView(): bool
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        // Verificar si el usuario tiene permiso para ver suscripciones
        return $authUser->can('viewAny', Subscription::class);
    }

    protected function getStats(): array
    {
        // Contar suscripciones creadas en los últimos 7 días
        $subscriptionsLastWeekCount = Subscription::query()
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        // Contar suscripciones creadas en este mes
        $subscriptionsThisMonthCount = Subscription::query()
            ->where('created_at', '>=', now('America/Caracas')->startOfMonth()->setTimezone('UTC'))
            ->count();

        // Contar suscripciones creadas en el mes pasado
        $subscriptionsLastMonthCount = Subscription::query()
            ->where('created_at', '>=', now('America/Caracas')->subMonth()->startOfMonth()->setTimezone('UTC'))
            ->where('created_at', '<', now('America/Caracas')->subMonth()->endOfMonth()->setTimezone('UTC'))
            ->count();

        return [
            Stat::make('Suscripciones últimos 7 días', $subscriptionsLastWeekCount),
            Stat::make('Suscripciones este mes', $subscriptionsThisMonthCount),
            Stat::make('Suscripciones mes pasado', $subscriptionsLastMonthCount),
        ];
    }
}
