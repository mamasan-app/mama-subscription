<?php

namespace App\Filament\Store\Resources\SubscriptionResource\Pages;

use App\Filament\Store\Resources\SubscriptionResource;
use App\Enums\SubscriptionStatusEnum;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;
use Filament\Facades\Filament;
use App\Models\User;
use Filament\Notifications\Notification;
use App\Models\Plan;

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

        // Obtener el servicio asociado a la suscripción
        $plan = Plan::find($data['service_id']); // Cambiado de 'Plan_id' a 'plan_id'

        if (!$plan) {
            throw new \Exception('El servicio seleccionado no se encontró.');
        }

        // Obtener los días gratuitos desde el servicio
        $freeDays = (int) $plan->free_days;  // Días gratuitos del servicio
        $gracePeriod = (int) $plan->grace_period;

        // Obtener la fecha y hora actual en Caracas
        $nowInCaracas = Carbon::now('America/Caracas');

        // Calcular el trial_ends_at sumando los días gratuitos del servicio
        $trialEndsAt = $nowInCaracas->clone()->addDays($freeDays);
        $expiresAt = $nowInCaracas->clone()->addDays($gracePeriod);

        // Llenar los datos adicionales de la suscripción
        $data['status'] = SubscriptionStatusEnum::OnTrial->value; // Estado de prueba
        $data['trial_ends_at'] = $trialEndsAt; // Fecha de finalización del trial
        $data['renews_at'] = $trialEndsAt->clone(); // Fecha de renovación (ajusta según la lógica de negocio)
        $data['expires_at'] = $expiresAt; // Fecha de expiración con el período de gracia

        return $data;
    }

}
