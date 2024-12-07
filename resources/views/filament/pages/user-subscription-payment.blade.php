<x-filament::page>
    <div class="space-y-4">
        <h2 class="text-2xl font-semibold">Detalles de la Suscripción</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Detalles de la Suscripción -->
            <div>
                <h3 class="text-xl font-medium">Información de la Suscripción</h3>
                <p><strong>Estado:</strong>
                    {{ $subscription->status ? ucfirst(str_replace('_', ' ', $subscription->status->value)) : 'Estado no disponible' }}
                </p>
                <p><strong>Fin del Período de Prueba:</strong>
                    {{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('d/m/Y') : 'No disponible' }}
                </p>
                <p><strong>Fecha de Expiración:</strong>
                    {{ $subscription->expires_at ? $subscription->expires_at->format('d/m/Y') : 'No disponible' }}</p>
                <p><strong>Precio:</strong> {{ $subscription->formattedPrice() }}</p>
            </div>

            <!-- Detalles del Servicio -->
            <div>
                <h3 class="text-xl font-medium">Información del Servicio</h3>
                @if ($subscription)
                    <p><strong>Nombre del Servicio:</strong> {{ $subscription->service_name }}</p>
                    <p><strong>Descripción:</strong> {{ $subscription->service_description }}</p>
                    <p><strong>Precio:</strong> {{ $subscription->formattedPrice() }}</p>
                    <p><strong>Días Gratuitos:</strong> {{ $subscription->service_free_days }} días</p>
                    <p><strong>Período de Gracia:</strong> {{ $subscription->service_grace_period }} días</p>
                @else
                    <p>No hay servicio disponible para esta suscripción.</p>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>