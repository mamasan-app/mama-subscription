<x-filament::page>
    <div class="space-y-6">

        <!-- Contenedores de Detalles -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Detalles de la Suscripción -->
            <div class="p-6 bg-blue-900 bg-opacity-50 rounded-lg">
                <h3 class="text-xl font-bold text-white mb-4">Información de la Suscripción</h3>
                <ul class="space-y-2">
                    <li>
                        <span class="font-semibold text-white">Estado:</span>
                        <span class="text-white">
                            {{ $subscription->status ? ucfirst(str_replace('_', ' ', $subscription->status->value)) : 'Estado no disponible' }}
                        </span>
                    </li>
                    <li>
                        <span class="font-semibold text-white">Fin del Período de Prueba:</span>
                        <span class="text-white">
                            {{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('d/m/Y') : 'No disponible' }}
                        </span>
                    </li>
                    <li>
                        <span class="font-semibold text-white">Fecha de Expiración:</span>
                        <span class="text-white">
                            {{ $subscription->expires_at ? $subscription->expires_at->format('d/m/Y') : 'No disponible' }}
                        </span>
                    </li>
                    <li>
                        <span class="font-semibold text-white">Precio:</span>
                        <span class="text-green-400 font-medium">
                            {{ $subscription->formattedPrice() }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Detalles del Servicio -->
            <div class="p-6 bg-blue-900 bg-opacity-50 rounded-lg">
                <h3 class="text-xl font-bold text-white mb-4">Información del Servicio</h3>
                @if ($subscription)
                    <ul class="space-y-2">
                        <li>
                            <span class="font-semibold text-white">Nombre del Servicio:</span>
                            <span class="text-white">{{ $subscription->service_name }}</span>
                        </li>
                        <li>
                            <span class="font-semibold text-white">Descripción:</span>
                            <span class="text-white">{{ $subscription->service_description }}</span>
                        </li>
                        <li>
                            <span class="font-semibold text-white">Precio:</span>
                            <span class="text-green-400 font-medium">{{ $subscription->formattedPrice() }}</span>
                        </li>
                        <li>
                            <span class="font-semibold text-white">Días Gratuitos:</span>
                            <span class="text-white">{{ $subscription->service_free_days }} días</span>
                        </li>
                        <li>
                            <span class="font-semibold text-white">Período de Gracia:</span>
                            <span class="text-white">{{ $subscription->service_grace_period }} días</span>
                        </li>
                    </ul>
                @else
                    <p class="text-gray-400">No hay servicio disponible para esta suscripción.</p>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>