<?php

namespace App\Filament\App\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Subscription;
use App\Models\Payment;
use App\Enums\SubscriptionStatusEnum;
use App\Enums\PaymentStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $userId = Auth::id();

        $subscriptions = Subscription::where('user_id', $userId)
            ->whereNotNull('renews_at')
            ->where('frequency_days', '>', 0)
            ->get();

        $events = [];

        foreach ($subscriptions as $subscription) {
            if ($subscription->status === SubscriptionStatusEnum::OnTrial) {
                // Mostrar solo la fecha de expiración del periodo de prueba
                if ($subscription->trial_ends_at->between($start, $end)) {
                    $events[] = [
                        'id' => $subscription->id,
                        'title' => $subscription->service_name . ' (Prueba)',
                        'start' => $subscription->trial_ends_at->toDateString(),
                        'color' => 'warning', // Amarillo para "En periodo de prueba"
                    ];
                }
            } elseif ($subscription->status === SubscriptionStatusEnum::Active) {
                $nextRenewal = Carbon::parse($subscription->renews_at);
                $limitDate = $subscription->ends_at
                    ? Carbon::parse($subscription->ends_at)->subDays($subscription->frequency_days)
                    : null;

                // Obtener la cantidad de pagos exitosos
                $completedPaymentsCount = Payment::where('subscription_id', $subscription->id)
                    ->where('status', PaymentStatusEnum::Completed)
                    ->whereBetween('date', [$start, $end])
                    ->count();

                $eventsToShow = 0;

                while ($nextRenewal->lessThanOrEqualTo($end)) {
                    // Para suscripciones finitas, respetar el límite de fecha.
                    if ($limitDate && $nextRenewal->greaterThan($limitDate)) {
                        break;
                    }

                    if ($completedPaymentsCount > 0) {
                        $completedPaymentsCount--;
                    } else {
                        $events[] = [
                            'id' => $subscription->id,
                            'title' => $subscription->service_name,
                            'start' => $nextRenewal->toDateString(),
                            'color' => 'success', // Verde para "Activa"
                        ];
                        $eventsToShow++;
                    }

                    // Incrementar la siguiente fecha dependiendo de la frecuencia
                    $nextRenewal->addDays($subscription->frequency_days);
                }
            }
        }

        return $events;
    }
}
