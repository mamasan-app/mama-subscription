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
                @if ($service)
                    <p><strong>Nombre del Servicio:</strong> {{ $service->name }}</p>
                    <p><strong>Descripción:</strong> {{ $service->description }}</p>
                    <p><strong>Precio:</strong> {{ $subscription->formattedPrice() }}</p>
                    <p><strong>Días Gratuitos:</strong> {{ $service->free_days }} días</p>
                    <p><strong>Período de Gracia:</strong> {{ $service->grace_period }} días</p>
                @else
                    <p>No hay servicio disponible para esta suscripción.</p>
                @endif
            </div>
        </div>

        <!-- Botón para iniciar el pago -->
        <div class="mt-6">
            <x-filament::button color="success" wire:click="processPayment">
                Iniciar Pago
            </x-filament::button>
        </div>
    </div>
</x-filament::page>