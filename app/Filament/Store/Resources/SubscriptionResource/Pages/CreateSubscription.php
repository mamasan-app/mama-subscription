<?php

namespace App\Filament\Store\Resources\SubscriptionResource\Pages;

use App\Enums\SubscriptionStatusEnum;
use App\Filament\Store\Resources\SubscriptionResource;
use App\Models\Frequency;
use App\Models\Plan;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtener la tienda actual desde el Tenant
        $currentStore = Filament::getTenant();

        if (! $currentStore) {
            throw new \Exception('No se ha encontrado una tienda actual para asociar la suscripción.');
        }

        // Asignar el store_id a la suscripción
        $data['store_id'] = $currentStore->id;

        // Obtener el Plan relacionado
        $plan = Plan::with('frequency')->find($data['service_id']); // Asegúrate de cargar la relación 'frequency'

        if (! $plan) {
            throw new \Exception('El servicio seleccionado no se encontró.');
        }

        // Obtener datos del Plan
        $freeDays = (int) $plan->free_days; // Días gratuitos del Plan
        $gracePeriod = (int) $plan->grace_period; // Período de gracia
        $serviceName = $plan->name;
        $serviceDescription = $plan->description;
        $servicePriceCents = $plan->price_cents;

        // Obtener datos de la frecuencia
        $frequencyDays = $plan->getFrequencyDays();

        if ($frequencyDays === 0) {
            throw new \Exception('La frecuencia asociada al plan no es válida.');
        }

        // Calcular fechas importantes
        $nowInCaracas = Carbon::now('America/Caracas');
        $trialEndsAt = $nowInCaracas->clone()->addDays($freeDays);
        $expiresAt = $nowInCaracas->clone()->addDays($gracePeriod + $freeDays);

        // Llenar datos adicionales
        $data['status'] = SubscriptionStatusEnum::OnTrial->value; // Estado inicial
        $data['trial_ends_at'] = $trialEndsAt;
        $data['renews_at'] = $trialEndsAt;
        $data['expires_at'] = $expiresAt;

        // Datos desnormalizados del Plan
        $data['service_name'] = $serviceName;
        $data['service_description'] = $serviceDescription;
        $data['service_price_cents'] = $servicePriceCents;
        $data['service_free_days'] = $freeDays;
        $data['service_grace_period'] = $gracePeriod;

        // Datos desnormalizados de la Frecuencia
        $data['frequency_days'] = $frequencyDays;

        return $data;
    }
}
