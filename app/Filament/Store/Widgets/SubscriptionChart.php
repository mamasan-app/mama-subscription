<?php

namespace App\Filament\Store\Widgets;

use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class SubscriptionChart extends ChartWidget
{
    protected static ?string $heading = 'Suscripciones';

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    protected static ?array $options = [
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
        'scales' => [
            'y' => [
                'ticks' => [
                    'stepSize' => 1,
                ],
                'suggestedMax' => 10,
            ],
        ],
    ];

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // Establecer el rango de fechas según el filtro seleccionado
        [$start, $end] = match ($activeFilter) {
            'last_month' => [
                now('America/Caracas')->subMonth()->startOfMonth()->setTimezone('UTC'),
                now('America/Caracas')->subMonth()->endOfMonth()->setTimezone('UTC'),
            ],
            'this_month' => [
                now('America/Caracas')->startOfMonth()->setTimezone('UTC'),
                now('America/Caracas')->endOfMonth()->setTimezone('UTC'),
            ],
            default => [now()->subWeek(), now()],
        };

        /** @var Store $currentStore */
        $currentStore = Filament::getTenant();

        // Si no hay una tienda en sesión, devolvemos datos vacíos
        if (! $currentStore) {
            return [
                'datasets' => [
                    [
                        'label' => 'Suscripciones',
                        'data' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        // Filtrar suscripciones para la tienda actual
        $dbData = Subscription::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', function ($query) use ($currentStore) {
                $query->where('store_id', $currentStore->id); // Solo servicios asociados a la tienda actual
            })
            ->orderBy('created_at')
            ->get('created_at')
            ->map(function (Subscription $subscription) {
                return [
                    'created_at' => $subscription->created_at->setTimezone('America/Caracas')->format('Y-m-d'),
                ];
            })
            ->groupBy('created_at')
            ->map(fn (Collection $group, string $key) => [
                'aggregate' => $group->count(),
                'date' => $key,
            ])
            ->values();

        /** @var Collection<int, Carbon> $period */
        $period = collect(iterator_to_array(CarbonPeriod::create($start, $end)));

        $data = $period->map(function (Carbon $carbonDate) use ($dbData) {
            $date = $carbonDate->format('Y-m-d');
            $existing = $dbData->firstWhere('date', $date);
            if ($existing) {
                return $existing;
            }

            return [
                'date' => $date,
                'aggregate' => 0,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Suscripciones',
                    'data' => $data->map(fn (array $value) => $value['aggregate']),
                ],
            ],
            'labels' => $data
                ->map(fn (array $value) => Carbon::createFromFormat('Y-m-d', $value['date'])?->format('M d')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';  // Gráfico de barras para las suscripciones
    }

    /**
     * @return string[]
     */
    protected function getFilters(): array
    {
        return [
            'last_week' => 'Últimos 7 días',
            'this_month' => 'Este mes',
            'last_month' => 'Mes pasado',
        ];
    }
}
