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

            // Invoice events
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
            case 'invoice.upcoming':
                $this->handleInvoiceUpcoming($eventData);
                break;
            case 'invoice.overdue':
                $this->handleInvoiceOverdue($eventData);
                break;
            case 'invoice.voided':
                $this->handleInvoiceVoided($eventData);
                break;

            // Payment Intent events
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($eventData);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($eventData);
                break;
            case 'payment_intent.canceled':
                $this->handlePaymentIntentCanceled($eventData);
                break;
            case 'payment_intent.created':
                $this->handlePaymentIntentCreated($eventData);
                break;
            case 'payment_intent.processing':
                $this->handlePaymentIntentProcessing($eventData);
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

    // Invoice handlers
    protected function handleInvoiceUpcoming($invoice)
    {
        Log::info('Invoice upcoming', ['invoice' => $invoice]);

        // Notificar al usuario
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
        if ($subscription) {
            // Aquí podrías enviar un correo o notificación al usuario.
            Log::info("Notificando factura próxima para suscripción: {$subscription->id}");
        }
    }

    protected function handleInvoiceOverdue($invoice)
    {
        Log::warning('Invoice overdue', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();
        if ($payment) {
            $payment->markAsOverdue();
        }
    }

    protected function handleInvoiceVoided($invoice)
    {
        Log::warning('Invoice voided', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();
        if ($payment) {
            $payment->markAsVoid();
        }
    }

    // Otros métodos (created, updated, payment_succeeded, payment_failed) permanecen igual.

    protected function handleInvoiceCreated($invoice)
    {
        Log::info('Invoice created', ['invoice' => $invoice]);

        $subscriptionId = $invoice->subscription ?? null;
        if (!$subscriptionId) {
            Log::error('No subscription ID found in invoice');
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::error("Subscription not found for Stripe subscription ID: {$subscriptionId}");
            return;
        }

        Payment::updateOrCreate(
            ['stripe_invoice_id' => $invoice->id],
            [
                'subscription_id' => $subscription->id,
                'status' => 'pending',
                'amount_cents' => $invoice->amount_due ?? 0,
                'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
            ]
        );

        Log::info("Payment created or updated for invoice ID: {$invoice->id}");
    }


    protected function handleInvoicePaid($invoice)
    {
        Log::info('Invoice paid', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'paid_date' => now(),
            ]);
            Log::info("Payment status updated to 'completed' for invoice ID: {$invoice->id}");
        } else {
            Log::warning("Payment not found for invoice ID: {$invoice->id}");
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

    // Payment Intent handlers
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Payment Intent succeeded', ['payment_intent' => $paymentIntent]);

        $payment = Payment::where('stripe_invoice_id', $paymentIntent->invoice)->first();

        if ($payment) {
            Transaction::createFromPaymentIntent($paymentIntent, $payment);
            $payment->markAsPaid();
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        Log::error('Payment Intent failed', ['payment_intent' => $paymentIntent]);

        $payment = Payment::where('stripe_invoice_id', $paymentIntent->invoice)->first();

        if ($payment) {
            Transaction::createFromPaymentIntent($paymentIntent, $payment);
            $payment->update(['status' => 'failed']);
        }
    }

    protected function handlePaymentIntentCanceled($paymentIntent)
    {
        Log::info('Payment Intent canceled', ['payment_intent' => $paymentIntent]);

        $payment = Payment::where('stripe_invoice_id', $paymentIntent->invoice)->first();

        if ($payment) {
            Transaction::createFromPaymentIntent($paymentIntent, $payment);
            $payment->update(['status' => 'canceled']);
        }
    }

    protected function handlePaymentIntentCreated($paymentIntent)
    {
        Log::info('Payment Intent created', ['payment_intent' => $paymentIntent]);
        $payment = Payment::where('stripe_invoice_id', $paymentIntent->invoice)->first();

        if ($payment) {
            Transaction::createFromPaymentIntent($paymentIntent, $payment);
            $payment->markAsPaid();
        }
    }

    protected function handlePaymentIntentProcessing($paymentIntent)
    {
        Log::info('Payment Intent processing', ['payment_intent' => $paymentIntent]);

        $payment = Payment::where('stripe_invoice_id', $paymentIntent->invoice)->first();

        if ($payment) {
            $payment->update(['status' => 'processing']);
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