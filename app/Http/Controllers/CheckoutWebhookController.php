<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Enums\TransactionTypeEnum; // Enum para tipos de transacción
use App\Enums\TransactionStatusEnum; // Enum para estatus de transacción
use Filament\Notifications\Notification; // Para las notificaciones
use Stripe\Webhook; // Para manejar los webhooks de Stripe
use Stripe\Exception\SignatureVerificationException;

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

        $subscriptionId = $session->metadata->subscription_id ?? null;

        if ($subscriptionId) {
            $subscription = Subscription::find($subscriptionId);
            if ($subscription) {
                $subscription->update([
                    'stripe_subscription_id' => $session->subscription,
                    'status' => 'active',
                ]);

                // Registrar transacción exitosa
                $subscription->transactions()->create([
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => TransactionStatusEnum::Succeeded->value,
                    'amount_cents' => $session->amount_total,
                    'metadata' => $session,
                ]);

                // Notificar al usuario
                Notification::make()
                    ->title('Pago exitoso')
                    ->body('Tu suscripción ha sido activada con éxito.')
                    ->success()
                    ->send();
            }
        }
    }


    protected function handleAsyncPaymentFailed($session)
    {
        Log::error('Async payment failed', ['session' => $session]);

        $subscriptionId = $session->metadata->subscription_id ?? null;

        if ($subscriptionId) {
            $subscription = Subscription::find($subscriptionId);
            if ($subscription) {
                $subscription->update([
                    'status' => 'payment_failed',
                ]);

                // Registrar transacción fallida
                $subscription->transactions()->create([
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => TransactionStatusEnum::Failed->value,
                    'amount_cents' => $session->amount_total,
                    'metadata' => $session,
                ]);

                // Notificar al usuario
                Notification::make()
                    ->title('Pago fallido')
                    ->body('No se pudo completar el pago de tu suscripción. Por favor, intenta nuevamente.')
                    ->danger()
                    ->send();
            }
        }
    }

    protected function handleSessionExpired($session)
    {
        Log::warning('Checkout session expired', ['session' => $session]);

        $subscriptionId = $session->metadata->subscription_id ?? null;

        if ($subscriptionId) {
            $subscription = Subscription::find($subscriptionId);
            if ($subscription) {
                $subscription->update([
                    'status' => 'expired',
                ]);

                // Notificar al usuario
                Notification::make()
                    ->title('Sesión expirada')
                    ->body('La sesión de pago ha expirado. Por favor, intenta nuevamente.')
                    ->warning()
                    ->send();
            }
        }
    }

}
