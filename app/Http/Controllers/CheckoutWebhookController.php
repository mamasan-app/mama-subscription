<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;

class CheckoutWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_checkout');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleSessionCompleted($event->data->object);
                break;
            case 'checkout.session.async_payment_succeeded':
                $this->handleAsyncPaymentSucceeded($event->data->object);
                break;
            case 'checkout.session.async_payment_failed':
                $this->handleAsyncPaymentFailed($event->data->object);
                break;
            case 'checkout.session.expired':
                $this->handleSessionExpired($event->data->object);
                break;
            default:
                Log::info("Unhandled event type: {$event->type}");
        }

        return response('Webhook handled', 200);
    }

    protected function handleSessionCompleted($session)
    {
        Log::info('Checkout session completed', ['session' => $session]);

        $subscriptionId = $session->metadata->subscription_id ?? null;

        if ($subscriptionId) {
            $subscription = Subscription::find($subscriptionId);
            if ($subscription) {
                $subscription->update([
                    'stripe_subscription_id' => $session->subscription,
                    'status' => 'active',
                ]);
            }
        }
    }

    protected function handleAsyncPaymentSucceeded($session)
    {
        Log::info('Async payment succeeded', ['session' => $session]);
        // Implementar lógica adicional si es necesario.
    }

    protected function handleAsyncPaymentFailed($session)
    {
        Log::error('Async payment failed', ['session' => $session]);
        // Implementar notificación o lógica de manejo de fallos.
    }

    protected function handleSessionExpired($session)
    {
        Log::warning('Checkout session expired', ['session' => $session]);
        // Manejar la expiración, como liberar productos reservados o notificar al usuario.
    }
}
