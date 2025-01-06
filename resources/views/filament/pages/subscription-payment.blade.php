<x-filament::page>
    <form wire:submit.prevent="submit">
        <!-- Renderiza el formulario -->
        {{ $this->form }}

        <!-- Botón de envío con margen superior -->
        <div class="mt-4">
            <x-filament::button type="submit">
                Procesar Pago
            </x-filament::button>
        </div>
    </form>
</x-filament::page>