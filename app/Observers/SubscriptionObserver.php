<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Models\User;
use Filament\Notifications\Notification;

class SubscriptionObserver
{
    /**
     * Handle the Subscription "created" event.
     */
    public function created(Subscription $subscription): void
    {
        // Obtener el cliente (user_id) asociado con la suscripción creada
        $client = User::find($subscription->user_id);

        // Verificar si el cliente existe
        if ($client) {
            // Crear una notificación con título y mensaje personalizado
            Notification::make()
                ->title('Subscripción Creada')
                ->body('Recuerda pagar el servicio antes del '.$subscription->expires_at->format('d/m/Y'))
                ->sendToDatabase($client);
        }
    }
}
