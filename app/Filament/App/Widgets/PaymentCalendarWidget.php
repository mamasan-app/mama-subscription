<?php

namespace App\Filament\App\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Subscription;
use Carbon\Carbon;

class PaymentCalendarWidget extends FullCalendarWidget
{
    protected function getEvents(): array
    {
        // Fechas del rango visible del calendario
        $start = Carbon::parse(request('start')); // Rango inicial visible
        $end = Carbon::parse(request('end'));     // Rango final visible

        $subscriptions = Subscription::whereNotNull('renews_at')
            ->where('frequency_days', '>', 0) // Asegurarse de que hay frecuencia
            ->get();

        $events = [];

        foreach ($subscriptions as $subscription) {
            $nextRenewal = Carbon::parse($subscription->renews_at);

            // Determinar la fecha límite de pagos (finita o infinita)
            $limitDate = $subscription->ends_at
                ? Carbon::parse($subscription->ends_at)->subDays($subscription->frequency_days)
                : null; // Sin límite si es infinita

            // Generar eventos dentro del rango visible del calendario
            while ($nextRenewal->between($start, $end)) {
                // Si es finita y la próxima renovación excede el límite, salir del loop
                if ($limitDate && $nextRenewal->greaterThan($limitDate)) {
                    break;
                }

                $events[] = [
                    'id' => $subscription->id,
                    'title' => $subscription->service_name,
                    'start' => $nextRenewal->toDateString(),
                    'color' => '#28a745', // Personaliza el color si es necesario
                ];

                // Avanzar a la siguiente renovación según la frecuencia
                $nextRenewal->addDays($subscription->frequency_days);
            }
        }

        return $events;
    }

    protected function getOptions(): array
    {
        return [
            'initialView' => 'dayGridMonth', // Vista inicial del calendario
            'headerToolbar' => [
                'start' => 'prev,next today',
                'center' => 'title',
                'end' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
        ];
    }
}
