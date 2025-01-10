<?php

namespace App\Filament\App\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Subscription;
use App\Enums\SubscriptionStatusEnum;
use Carbon\Carbon;

class PaymentCalendarWidget extends FullCalendarWidget
{
    /**
     * Fetch events for the calendar.
     * This is triggered when the user interacts with the calendar (prev/next).
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start']);
        $end = Carbon::parse($fetchInfo['end']);

        $subscriptions = Subscription::whereNotNull('renews_at')
            ->where('frequency_days', '>', 0)
            ->get();

        $events = [];

        foreach ($subscriptions as $subscription) {
            if ($subscription->status === SubscriptionStatusEnum::OnTrial) {
                // Si está en periodo de prueba, solo mostrar la fecha de trial_ends_at si está dentro del rango visible
                if ($subscription->trial_ends_at->between($start, $end)) {
                    $events[] = [
                        'id' => $subscription->id,
                        'title' => $subscription->service_name . ' (Prueba)',
                        'start' => $subscription->trial_ends_at->toDateString(),
                        'color' => '#ffc107', // Amarillo para "En periodo de prueba"
                    ];
                }
            } else {
                // Para otros estados, calcular los eventos recurrentes como antes
                $nextRenewal = Carbon::parse($subscription->renews_at);

                // Límite de pagos (para suscripciones finitas)
                $limitDate = $subscription->ends_at
                    ? Carbon::parse($subscription->ends_at)->subDays($subscription->frequency_days)
                    : null;

                while ($nextRenewal->between($start, $end)) {
                    if ($limitDate && $nextRenewal->greaterThan($limitDate)) {
                        break;
                    }

                    $events[] = [
                        'id' => $subscription->id,
                        'title' => $subscription->service_name,
                        'start' => $nextRenewal->toDateString(),
                        'color' => '#28a745', // Verde para "Activa"
                    ];

                    $nextRenewal->addDays($subscription->frequency_days);
                }
            }
        }

        return $events;
    }

}
