<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeBalanceWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('Stripe-Signature');

        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                config('stripe.webhook_secret') // Asegúrate de configurar esto en el .env
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid payload', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid signature', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Manejar el evento balance.available
        if ($event->type === 'balance.available') {
            $this->handleBalanceAvailable($event->data->object);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleBalanceAvailable($balance)
    {
        // Aquí puedes implementar la lógica necesaria para manejar el balance
        Log::info('Balance disponible actualizado', ['balance' => $balance]);

        // Ejemplo: Guardar en la base de datos o notificar a un usuario
        // $balance['available'] contiene el balance disponible
        foreach ($balance->available as $available) {
            Log::info("Currency: {$available->currency}, Amount: {$available->amount}");
        }
    }
}
