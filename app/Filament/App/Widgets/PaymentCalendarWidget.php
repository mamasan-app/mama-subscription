<?php

namespace App\Filament\App\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Subscription;
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
            ->where('frequency_days', '>', 0) // Ignorar suscripciones sin frecuencia
            ->get();

        $events = [];

        foreach ($subscriptions as $subscription) {
            $nextRenewal = Carbon::parse($subscription->renews_at);

            // Límite de pagos (para suscripciones finitas)
            $limitDate = $subscription->ends_at
                ? Carbon::parse($subscription->ends_at)->subDays($subscription->frequency_days)
                : null;

            while ($nextRenewal->between($start, $end)) {
                // Si la suscripción es finita y excede el límite, romper el bucle
                if ($limitDate && $nextRenewal->greaterThan($limitDate)) {
                    break;
                }

                $events[] = [
                    'id' => $subscription->id,
                    'title' => $subscription->service_name,
                    'start' => $nextRenewal->toDateString(),
                    'color' => '#28a745', // Personaliza el color según el estado
                ];

                // Avanzar a la siguiente renovación según la frecuencia
                $nextRenewal->addDays($subscription->frequency_days);
            }
        }

        return $events;
    }
}
