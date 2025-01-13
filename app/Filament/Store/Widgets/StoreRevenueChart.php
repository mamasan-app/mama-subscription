<?php

namespace App\Filament\Store\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Payment;
use Filament\Facades\Filament;

class StoreRevenueChart extends LineChartWidget
{
    protected static ?string $heading = 'Gráfico de Ingresos de la Tienda';

    protected function getData(): array
    {
        // Obtener el store_id del tenant actual
        $currentStore = Filament::getTenant();

        if (!$currentStore) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Obtener ingresos agrupados por mes en el último año
        $revenueData = Payment::whereHas('subscription', function ($query) use ($currentStore) {
            $query->where('store_id', $currentStore->id);
        })
            ->where('status', 'completed') // Solo pagos completados
            ->whereBetween('created_at', [now()->subYear(), now()]) // Últimos 12 meses
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount_cents) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Preparar datos para el gráfico
        $labels = [];
        $data = [];

        foreach ($revenueData as $item) {
            $labels[] = $item->month;
            $data[] = $item->total / 100; // Convertir de centavos a dólares
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $data,
                    'borderColor' => '#3b82f6', // Color de la línea
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)', // Fondo debajo de la línea
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
