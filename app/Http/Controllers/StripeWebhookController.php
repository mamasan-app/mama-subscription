<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionStatusEnum;
use Filament\Notifications\Notification;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        $eventType = $event->type;
        $eventData = $event->data->object;

        Log::info("Stripe Webhook received: {$eventType}");

        switch ($eventType) {
            // Checkout events
            case 'checkout.session.completed':
                $this->handleSessionCompleted($eventData);
                break;
            case 'checkout.session.async_payment_succeeded':
                $this->handleAsyncPaymentSucceeded($eventData);
                break;
            case 'checkout.session.async_payment_failed':
                $this->handleAsyncPaymentFailed($eventData);
                break;
            case 'checkout.session.expired':
                $this->handleSessionExpired($eventData);
                break;

            case 'invoice.created':
                $this->handleInvoiceCreated($eventData);
                break;
            case 'invoice.updated':
                $this->handleInvoiceUpdated($eventData);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($eventData);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($eventData);
                break;

            default:
                Log::info("Unhandled event type: {$eventType}");
        }

        return response('Webhook handled', 200);
    }

    // Checkout handlers
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

                Log::info('Subscription updated', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $session->subscription,
                ]);
            } else {
                Log::error('Subscription not found', ['subscription_id' => $subscriptionId]);
            }
        } else {
            Log::error('Subscription ID missing in session metadata', ['session' => $session]);
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

    protected function handleInvoiceCreated($invoice)
    {
        Log::info('Invoice created event received', [
            'invoice_id' => $invoice->id ?? 'N/A',
            'subscription_id' => $invoice->subscription ?? 'N/A',
            'amount_due' => $invoice->amount_due ?? 0,
            'due_date' => $invoice->due_date ?? 'N/A',
        ]);

        $subscriptionId = $invoice->subscription ?? null;
        if (!$subscriptionId) {
            Log::error('No subscription ID found in invoice', [
                'invoice_id' => $invoice->id ?? 'N/A',
            ]);
            return;
        }

        // Buscar la suscripción en la base de datos
        Log::info('Searching for subscription', ['stripe_subscription_id' => $subscriptionId]);
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (!$subscription) {
            Log::error('Subscription not found in database', [
                'stripe_subscription_id' => $subscriptionId,
                'invoice_id' => $invoice->id ?? 'N/A',
            ]);
            return;
        }

        Log::info('Subscription found', [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->stripe_subscription_id,
        ]);

        try {
            // Buscar si ya existe un registro de Payment
            Log::info('Searching for existing payment', [
                'stripe_invoice_id' => $invoice->id,
            ]);
            $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

            if (!$payment) {
                Log::info('No existing payment found. Creating new payment...', [
                    'stripe_invoice_id' => $invoice->id,
                ]);
                $payment = Payment::create([
                    'stripe_invoice_id' => $invoice->id,
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'amount_cents' => $invoice->amount_due ?? 0,
                    'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
                ]);
                Log::info('Payment created successfully', ['payment_id' => $payment->id]);
            } else {
                Log::info('Existing payment found. Updating payment...', [
                    'payment_id' => $payment->id,
                    'stripe_invoice_id' => $invoice->id,
                ]);
                $payment->update([
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'amount_cents' => $invoice->amount_due ?? 0,
                    'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
                ]);
                Log::info('Payment updated successfully', ['payment_id' => $payment->id]);
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while creating or updating payment', [
                'invoice_id' => $invoice->id,
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function handleInvoiceUpdated($invoice)
    {
        Log::info('Invoice updated', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->update([
                'status' => $invoice->status,
                'amount_cents' => $invoice->amount_due,
                'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
            ]);
        }
    }

    protected function handleInvoicePaymentSucceeded($invoice)
    {
        Log::info('Invoice payment succeeded', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->markAsPaid();
        }
    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        Log::error('Invoice payment failed', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->update(['status' => 'failed']);
        }
    }

}


//
//namespace App\Http\Controllers;
//
//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;
//use App\Models\Payment;
//use App\Models\Transaction;
//use Carbon\Carbon;
//
//class StripeWebhookController extends Controller
//{
//    public function handle(Request $request)
//    {
//        $payload = $request->all();
//        $signature = $request->header('Stripe-Signature');
//
//        // Verificar la firma del webhook
//        try {
//            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
//            $event = \Stripe\Webhook::constructEvent(
//                $request->getContent(),
//                $signature,
//                config('stripe.webhook_secret')
//            );
//        } catch (\Exception $e) {
//            Log::error('Stripe webhook signature verification failed', ['exception' => $e->getMessage()]);
//            return response()->json(['error' => 'Invalid signature'], 400);
//        }
//
//        // Manejar los eventos específicos
//        Log::info("Evento recibido: {$event->type}");
//
//        switch ($event->type) {
//            case 'checkout.session.completed':
//                $this->handleCheckoutSessionCompleted($event->data->object);
//                break;
//            case 'invoice.payment_succeeded':
//                $this->handlePaymentSucceeded($event->data->object);
//                break;
//            case 'invoice.payment_failed':
//                $this->handlePaymentFailed($event->data->object);
//                break;
//            case 'subscription.updated':
//                $this->handleSubscriptionUpdated($event->data->object);
//                break;
//        }
//
//        return response()->json(['status' => 'success']);
//    }
//
//    protected function handleCheckoutSessionCompleted($session)
//    {
//        // Verificar si el checkout session tiene una suscripción asociada
//        if (!isset($session->subscription)) {
//            Log::warning("Checkout session sin suscripción asociada: {$session->id}");
//            return;
//        }
//
//        $subscriptionId = $session->subscription;
//
//        // Buscar la suscripción local con el ID proporcionado en el metadata
//        if (!isset($session->metadata->subscription_id)) {
//            Log::warning("Checkout session sin metadata de subscription_id: {$session->id}");
//            return;
//        }
//
//        $localSubscriptionId = $session->metadata->subscription_id;
//
//        $localSubscription = \App\Models\Subscription::find($localSubscriptionId);
//
//        if (!$localSubscription) {
//            Log::warning("No se encontró la suscripción local para el ID: {$localSubscriptionId}");
//            return;
//        }
//
//        // Actualizar el stripe_subscription_id en la suscripción local
//        $localSubscription->update([
//            'stripe_subscription_id' => $subscriptionId,
//        ]);
//
//        Log::info("Suscripción actualizada con stripe_subscription_id: {$subscriptionId}");
//    }
//
//
//
//    protected function handlePaymentSucceeded($invoice)
//    {
//        // Obtener el ID del Payment asociado desde el metadata
//        $paymentId = $invoice->metadata->payment_id ?? null;
//
//        if ($paymentId) {
//            $payment = Payment::find($paymentId);
//
//            if ($payment) {
//                $payment->update([
//                    'status' => 'approved',
//                    'paid_date' => Carbon::now(),
//                ]);
//
//                // Actualizar transacción asociada
//                $transaction = $payment->transactions()->first();
//                if ($transaction) {
//                    $transaction->update([
//                        'status' => 'completed',
//                    ]);
//                }
//            }
//        }
//    }
//
//    protected function handlePaymentFailed($invoice)
//    {
//        $paymentId = $invoice->metadata->payment_id ?? null;
//
//        if ($paymentId) {
//            $payment = Payment::find($paymentId);
//
//            if ($payment) {
//                $payment->update(['status' => 'failed']);
//            }
//        }
//    }
//
//    protected function handleSubscriptionUpdated($subscription)
//    {
//        // Buscar la suscripción local usando el stripe_subscription_id
//        $localSubscription = \App\Models\Subscription::where('stripe_subscription_id', $subscription->id)->first();
//
//        if (!$localSubscription) {
//            \Log::warning("No se encontró una suscripción local para el stripe_subscription_id: {$subscription->id}");
//            return;
//        }
//
//        // Mapear los estados de Stripe a los estados de tu sistema
//        $statusMap = [
//            'active' => \App\Enums\SubscriptionStatusEnum::Active->value,
//            'past_due' => \App\Enums\SubscriptionStatusEnum::PastDue->value,
//            'canceled' => \App\Enums\SubscriptionStatusEnum::Cancelled->value,
//            'incomplete' => \App\Enums\SubscriptionStatusEnum::Pending->value,
//            'incomplete_expired' => \App\Enums\SubscriptionStatusEnum::Expired->value,
//            'trialing' => \App\Enums\SubscriptionStatusEnum::OnTrial->value,
//            'unpaid' => \App\Enums\SubscriptionStatusEnum::PastDue->value,
//        ];
//
//        // Actualizar el estado de la suscripción en tu sistema
//        $localSubscription->update([
//            'status' => $statusMap[$subscription->status] ?? \App\Enums\SubscriptionStatusEnum::Pending->value,
//            'trial_ends_at' => $subscription->trial_end ? \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
//            'renews_at' => $subscription->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end) : null,
//            'ends_at' => $subscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at) : null,
//            'expires_at' => $subscription->cancel_at_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end) : null,
//        ]);
//
//        \Log::info("Suscripción actualizada: {$localSubscription->id}", [
//            'stripe_subscription_id' => $subscription->id,
//            'status' => $subscription->status,
//        ]);
//    }
//
//
//}