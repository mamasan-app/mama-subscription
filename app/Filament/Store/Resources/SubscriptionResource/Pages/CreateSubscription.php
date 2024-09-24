<?php

namespace App\Filament\Store\Resources\SubscriptionResource\Pages;

use App\Filament\Store\Resources\SubscriptionResource;
use App\Enums\SubscriptionStatusEnum;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;
use Filament\Facades\Filament;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtener la tienda actual desde el Tenant
        $currentStore = Filament::getTenant();

        // Verifica que se haya obtenido el store_id correctamente
        if ($currentStore) {
            $data['store_id'] = $currentStore->id;
        } else {
            // Manejo en caso de no encontrar una tienda actual
            throw new \Exception('No se ha encontrado una tienda actual para asociar la suscripción.');
        }

        // Asegurarse de que 'days' y 'days_expired' sean enteros
        $days = (int) $data['days'];  // Convertir a entero
        $daysExpired = (int) $data['days_expired'];  // Convertir a entero

        // Obtener la fecha y hora actual en Caracas
        $nowInCaracas = Carbon::now('America/Caracas');

        // Calcular el trial_ends_at sumando los días gratuitos
        $trialEndsAt = $nowInCaracas->addDays($days);

        // Calcular el expires_at sumando el período de gracia a la fecha de vencimiento del trial
        $expiresAt = $trialEndsAt->clone()->addDays($daysExpired);

        // Llenar los datos adicionales de la suscripción
        $data['status'] = SubscriptionStatusEnum::OnTrial->value; // Estado de prueba
        $data['trial_ends_at'] = $trialEndsAt; // Fecha de finalización del trial
        $data['renews_at'] = $trialEndsAt->clone(); // Fecha de renovación (ajusta según la lógica de negocio)
        $data['expires_at'] = $expiresAt; // Fecha de expiración con el período de gracia

        return $data;
    }
}
