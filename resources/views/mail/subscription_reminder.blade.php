<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de pago</title>
</head>

<body>
    <h2>Hola {{ $subscription->user->name }},</h2>
    <p>Este es un recordatorio de que tu suscripción al servicio <strong>{{ $subscription->service_name }}</strong>
        vence el <strong>{{ $subscription->renews_at->format('d/m/Y') }}</strong>.</p>
    <p>El monto a pagar es de <strong>{{ number_format($subscription->service_price_cents / 100, 2) }} Bs.</strong></p>
    <p>Por favor, realiza el pago antes de la fecha de vencimiento para evitar la interrupción del servicio.</p>
    <p><a href="{{ url('/pagos') }}">Haz clic aquí para pagar</a></p>
    <p>Gracias por confiar en nosotros.</p>
</body>

</html>