<x-filament::page>
    <!-- Contenedor principal del formulario -->
    <div class="p-6 bg-gray-800 rounded-lg shadow space-y-6">
        <form wire:submit.prevent="submit">
            <!-- Contenedor interno del formulario -->
            <div class="space-y-4">
                {{ $this->form }}
            </div>
        </form>
    </div>
</x-filament::page>