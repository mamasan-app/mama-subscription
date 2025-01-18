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

        <div>
            <!-- Modal para Confirmar OTP -->
            <div x-data="{ open: false, bank: '', phone: '', identity: '', amount: '', otp: '' }"
                @open-confirm-otp-modal.window="
            open = true;
            bank = $event.detail.bank;
            phone = $event.detail.phone;
            identity = $event.detail.identity;
            amount = $event.detail.amount;
        " x-show="open" class="fixed inset-0 flex items-center justify-center z-50 bg-gray-900 bg-opacity-50">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm">
                    <h2 class="text-lg font-bold mb-4">Confirmar OTP</h2>
                    <p class="mb-2 text-sm text-gray-600">Banco: <span x-text="bank"></span></p>
                    <p class="mb-2 text-sm text-gray-600">Teléfono: <span x-text="phone"></span></p>
                    <p class="mb-2 text-sm text-gray-600">Identidad: <span x-text="identity"></span></p>
                    <p class="mb-4 text-sm text-gray-600">Monto: $<span x-text="amount"></span></p>

                    <form wire:submit.prevent="confirmOtp">
                        <div class="mb-4">
                            <label for="otp" class="block text-sm font-medium text-gray-700">Código OTP</label>
                            <input type="text" id="otp" x-model="otp" wire:model.defer="otp"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" @click="open = false"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Confirmar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-filament::page>