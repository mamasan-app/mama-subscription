<x-mail::message>
    # Confirmación de Correo

    Hola, por favor confirma tu correo electrónico haciendo clic en el botón de abajo.

    <x-mail::button :url="'http://example.com/confirm-email'">
        Confirmar Correo
    </x-mail::button>

    Gracias,<br>
    {{ config('app.name') }}
</x-mail::message>