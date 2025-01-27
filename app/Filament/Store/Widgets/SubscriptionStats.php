<?php

declare(strict_types=1);

namespace App\Filament\Store\Widgets;

use App\Models\Subscription;
use App\Models\User;
use Filament\Facades\Filament;
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
        /** @var Store|null $currentStore */
        $currentStore = Filament::getTenant();

        // Si no hay tienda activa, devolver valores vacíos
        if (! $currentStore) {
            return [
                Stat::make('Suscripciones últimos 7 días', 0),
                Stat::make('Suscripciones este mes', 0),
                Stat::make('Suscripciones mes pasado', 0),
            ];
        }

        // Contar suscripciones creadas en los últimos 7 días para la tienda activa
        $subscriptionsLastWeekCount = Subscription::query()
            ->whereHas('service', function (Builder $query) use ($currentStore) {
                $query->where('store_id', $currentStore->id);
            })
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        // Contar suscripciones creadas este mes para la tienda activa
        $subscriptionsThisMonthCount = Subscription::query()
            ->whereHas('service', function (Builder $query) use ($currentStore) {
                $query->where('store_id', $currentStore->id);
            })
            ->where('created_at', '>=', now('America/Caracas')->startOfMonth()->setTimezone('UTC'))
            ->count();

        // Contar suscripciones creadas en el mes pasado para la tienda activa
        $subscriptionsLastMonthCount = Subscription::query()
            ->whereHas('service', function (Builder $query) use ($currentStore) {
                $query->where('store_id', $currentStore->id);
            })
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
