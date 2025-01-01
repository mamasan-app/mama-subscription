<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Store;
use App\Models\Plan;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Jobs\RetryUpdateTransaction;
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
            case 'invoice.upcoming':
                $this->handleInvoiceUpcoming($eventData);
                break;
            case 'invoice.finalized':
                $this->handleInvoiceFinalized($eventData);
                break;


            case 'payment_intent.created':
                $this->handlePaymentIntentCreated($eventData);
                break;
            case 'payment_intent.processing':
                $this->handlePaymentIntentProcessing($eventData);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($eventData);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($eventData);
                break;
            case 'payment_intent.canceled':
                $this->handlePaymentIntentCanceled($eventData);
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

                $plan = Plan::find($subscription->service_id);

                if ($plan) {
                    // Verifica si el plan es finito
                    if (!$plan->infinite_duration) {
                        $endDate = now()->addDays($plan->duration)->toDateString();

                        try {
                            // Actualiza la suscripción en Stripe
                            \Stripe\Subscription::update($session->subscription, [
                                'cancel_at' => strtotime($endDate),
                            ]);

                            Log::info('Stripe subscription updated with cancel_at', [
                                'stripe_subscription_id' => $session->subscription,
                                'cancel_at' => $endDate,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to update Stripe subscription', [
                                'stripe_subscription_id' => $session->subscription,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } else {
                    Log::error('Plan not found for subscription', [
                        'service_id' => $subscription->service_id,
                    ]);
                }
            } else {
                Log::error('Subscription not found', ['subscription_id' => $subscriptionId]);
            }
        } else {
            Log::error('Subscription ID missing in session metadata', ['session' => $session]);
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
            Log::error('No subscription ID found in invoice', ['invoice_id' => $invoice->id ?? 'N/A']);
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::error('Subscription not found in database', [
                'stripe_subscription_id' => $subscriptionId,
                'invoice_id' => $invoice->id ?? 'N/A',
            ]);
            return;
        }

        try {
            $dueDate = isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null;

            $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

            if (!$payment) {
                $payment = Payment::updateOrCreate(
                    ['stripe_invoice_id' => $invoice->id],
                    [
                        'subscription_id' => $subscription->id,
                        'status' => 'pending',
                        'amount_cents' => $invoice->amount_due ?? 0,
                        'due_date' => $dueDate,
                    ]
                );

                // Obtener todas las transacciones asociadas al invoice
                $transactions = Transaction::where('stripe_invoice_id', $invoice->id)->get();

                foreach ($transactions as $transaction) {
                    $transaction->update([
                        'payment_id' => $payment->id,
                        'to_type' => $subscription->service && $subscription->service->store ? get_class($subscription->service->store) : null,
                        'to_id' => $subscription->service && $subscription->service->store ? $subscription->service->store->id : null,
                    ]);
                }


                Log::info('Payment y transacciones actualizados', [
                    'payment_id' => $payment->id,
                    'transaction_count' => $transactions->count(),
                ]);

            }

        } catch (\Exception $e) {
            Log::error('Exception occurred while creating or updating payment', [
                'invoice_id' => $invoice->id,
                'exception_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    protected function handleInvoiceUpdated($invoice)
    {
        Log::info('Invoice updated', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            try {
                // Usa el método fromStripeStatus para mapear el estado.
                $status = PaymentStatusEnum::fromStripeStatus($invoice->status);

                $payment->update([
                    'status' => $status,
                    'amount_cents' => $invoice->amount_due,
                    'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
                ]);
            } catch (\Exception $e) {
                Log::error('Error al procesar el estado de la factura', [
                    'invoice_id' => $invoice->id,
                    'status' => $invoice->status,
                    'exception_message' => $e->getMessage(),
                ]);
            }
        } else {
            $subscriptionId = $invoice->subscription ?? null;

            if (!$subscriptionId) {
                Log::error('No subscription ID found in invoice', ['invoice_id' => $invoice->id ?? 'N/A']);
                return;
            }

            $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

            $dueDate = isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null;

            $payment = Payment::updateOrCreate(
                ['stripe_invoice_id' => $invoice->id],
                [
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'amount_cents' => $invoice->amount_due ?? 0,
                    'due_date' => $dueDate,
                ]
            );

            // Obtener todas las transacciones asociadas al invoice
            $transactions = Transaction::where('stripe_invoice_id', $invoice->id)->get();

            foreach ($transactions as $transaction) {
                $transaction->update([
                    'payment_id' => $payment->id,
                    'to_type' => $subscription->service && $subscription->service->store ? get_class($subscription->service->store) : null,
                    'to_id' => $subscription->service && $subscription->service->store ? $subscription->service->store->id : null,
                ]);
            }


            Log::info('Payment y transacciones actualizados', [
                'payment_id' => $payment->id,
                'transaction_count' => $transactions->count(),
            ]);
        }
    }

    protected function handleInvoicePaymentSucceeded($invoice)
    {
        Log::info('Invoice payment succeeded', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->markAsPaid();
        } else {
            $subscriptionId = $invoice->subscription ?? null;

            if (!$subscriptionId) {
                Log::error('No subscription ID found in invoice', ['invoice_id' => $invoice->id ?? 'N/A']);
                return;
            }

            $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

            $dueDate = isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null;

            $payment = Payment::updateOrCreate(
                ['stripe_invoice_id' => $invoice->id],
                [
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'amount_cents' => $invoice->amount_due ?? 0,
                    'due_date' => $dueDate,
                ]
            );

            // Obtener todas las transacciones asociadas al invoice
            $transactions = Transaction::where('stripe_invoice_id', $invoice->id)->get();

            foreach ($transactions as $transaction) {
                $transaction->update([
                    'payment_id' => $payment->id,
                    'to_type' => $subscription->service && $subscription->service->store ? get_class($subscription->service->store) : null,
                    'to_id' => $subscription->service && $subscription->service->store ? $subscription->service->store->id : null,
                ]);
            }

            $payment->markAsPaid();
        }

        $subscription->update([
            'renews_at' => now()->addDays($subscription->frequency_days)->toDateString(),
        ]);

    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        Log::error('Invoice payment failed', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->update(['status' => 'failed']);
        } else {

            $subscriptionId = $invoice->subscription ?? null;

            if (!$subscriptionId) {
                Log::error('No subscription ID found in invoice', ['invoice_id' => $invoice->id ?? 'N/A']);
                return;
            }

            $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

            $dueDate = isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null;

            $payment = Payment::updateOrCreate(
                ['stripe_invoice_id' => $invoice->id],
                [
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'amount_cents' => $invoice->amount_due ?? 0,
                    'due_date' => $dueDate,
                ]
            );

            // Obtener todas las transacciones asociadas al invoice
            $transactions = Transaction::where('stripe_invoice_id', $invoice->id)->get();

            foreach ($transactions as $transaction) {
                $transaction->update([
                    'payment_id' => $payment->id,
                    'to_type' => $subscription->service && $subscription->service->store ? get_class($subscription->service->store) : null,
                    'to_id' => $subscription->service && $subscription->service->store ? $subscription->service->store->id : null,
                ]);
            }

            $payment->update(['status' => 'failed']);
        }
    }

    protected function handleInvoiceUpcoming($invoice)
    {
        Log::info('Invoice upcoming', ['invoice_id' => $invoice->id, 'due_date' => $invoice->due_date]);

        $subscriptionId = $invoice->subscription ?? null;

        if (!$subscriptionId) {
            Log::warning('No subscription ID found in upcoming invoice', ['invoice_id' => $invoice->id]);
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            // Obtener el usuario asociado a la suscripción
            $user = $subscription->user;

            if ($user) {
                // Validar que `due_date` no sea null
                $dueDate = $subscription->renews_at;

                // Enviar notificación al usuario
                $user->notify(new \App\Notifications\InvoiceUpcomingNotification($invoice, $dueDate));

                Log::info('Notification sent to user', ['user_id' => $user->id, 'invoice_id' => $invoice->id]);
            } else {
                Log::warning('No user found for subscription', ['subscription_id' => $subscriptionId]);
            }
        } else {
            Log::warning('Subscription not found for upcoming invoice', ['subscription_id' => $subscriptionId]);
        }
    }


    protected function handleInvoiceFinalized($invoice)
    {
        Log::info('Invoice finalized', ['invoice_id' => $invoice->id]);

        $subscriptionId = $invoice->subscription ?? null;

        if (!$subscriptionId) {
            Log::warning('No subscription ID found in finalized invoice', ['invoice_id' => $invoice->id]);
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            $payment = Payment::updateOrCreate(
                ['stripe_invoice_id' => $invoice->id],
                [
                    'subscription_id' => $subscription->id,
                    'status' => PaymentStatusEnum::Finalized->value, // Usando el enum para el estado.
                    'amount_cents' => $invoice->amount_due ?? 0,
                    'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
                ]
            );

            Log::info('Payment updated for finalized invoice', ['payment_id' => $payment->id]);
        } else {
            Log::warning('Subscription not found for finalized invoice', ['subscription_id' => $subscriptionId]);
        }
    }


    //Payment Intent
    protected function handlePaymentIntentCreated($paymentIntent)
    {
        Log::info('Payment Intent succeeded', ['payment_intent' => $paymentIntent]);

        $invoiceId = $paymentIntent->invoice ?? null;
        $customerId = $paymentIntent->customer;

        $transaction = Transaction::where('stripe_invoice_id', $invoiceId)->first();

        if ($invoiceId && !$transaction) {
            $payment = Payment::where('stripe_invoice_id', $invoiceId)->first();
            $customer = User::where('stripe_customer_id', $customerId)->first();
            if ($payment) {
                $subscription = Subscription::where('id', $payment->subscription_id)->first();
                $store = Store::where('id', $subscription->store_id)->first();
                Transaction::create([
                    'from_type' => get_class($customer), // Valor temporal hasta que se cree el invoice
                    'from_id' => $customer ? $customer->id : null, // Asignar el ID del cliente si está disponible
                    'to_type' => get_class($store), // Valor temporal hasta que se cree el invoice
                    'to_id' => $store, // Valor temporal hasta que se cree el invoice
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => Transaction::mapStripeStatusToLocal($paymentIntent->status),
                    'date' => now(),
                    'amount_cents' => $paymentIntent->amount,
                    'metadata' => $paymentIntent->toArray(),
                    'payment_id' => $payment ? $payment->id : null,
                    'stripe_payment_id' => $paymentIntent->id,
                    'stripe_invoice_id' => $invoiceId,
                ]);
            } else {
                Transaction::create([
                    'from_type' => get_class($customer), // Valor temporal hasta que se cree el invoice
                    'from_id' => $customer ? $customer->id : null, // Asignar el ID del cliente si está disponible
                    'to_type' => null, // Valor temporal hasta que se cree el invoice
                    'to_id' => null, // Valor temporal hasta que se cree el invoice
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => Transaction::mapStripeStatusToLocal($paymentIntent->status),
                    'date' => now(),
                    'amount_cents' => $paymentIntent->amount,
                    'metadata' => $paymentIntent->toArray(),
                    'payment_id' => $payment ? $payment->id : null,
                    'stripe_payment_id' => $paymentIntent->id,
                    'stripe_invoice_id' => $invoiceId,
                ]);
            }
            Log::info('Transacción creada/actualizada con éxito', ['payment_intent_id' => $paymentIntent->id]);
        } else {
            Log::error('Invoice ID no encontrado en PaymentIntent', ['payment_intent' => $paymentIntent]);
        }
    }

    protected function handlePaymentIntentProcessing($paymentIntent)
    {
        Log::info('Payment Intent processing', ['payment_intent' => $paymentIntent]);

        $this->updateTransactionStatus($paymentIntent, TransactionStatusEnum::Processing);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Payment Intent succeeded', ['payment_intent' => $paymentIntent]);

        $invoiceId = $paymentIntent->invoice ?? null;
        $customerId = $paymentIntent->customer;
        $payment = Payment::where('stripe_invoice_id', $invoiceId)->first();

        if ($invoiceId) {
            $transaction = Transaction::where('stripe_invoice_id', $invoiceId)->first();
            $customer = User::where('stripe_customer_id', $customerId)->first();
            if ($transaction) {
                $this->updateTransactionStatus($paymentIntent, TransactionStatusEnum::Succeeded);
                Log::info('Transacción creada/actualizada con éxito', ['payment_intent_id' => $paymentIntent->id]);
            } else {
                Transaction::create([
                    'from_type' => get_class($customer), // Valor temporal hasta que se cree el invoice
                    'from_id' => $customer ? $customer->id : null, // Asignar el ID del cliente si está disponible
                    'to_type' => null, // Valor temporal hasta que se cree el invoice
                    'to_id' => null, // Valor temporal hasta que se cree el invoice
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => Transaction::mapStripeStatusToLocal($paymentIntent->status),
                    'date' => now(),
                    'amount_cents' => $paymentIntent->amount,
                    'metadata' => $paymentIntent->toArray(),
                    'payment_id' => $payment ? $payment->id : null,
                    'stripe_payment_id' => $paymentIntent->id,
                    'stripe_invoice_id' => $invoiceId,
                ]);
            }
        } else {
            Log::error('Invoice ID no encontrado en PaymentIntent', ['payment_intent' => $paymentIntent]);
        }
    }


    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $invoiceId = $paymentIntent->invoice ?? null;
        $customerId = $paymentIntent->customer;
        $payment = Payment::where('stripe_invoice_id', $invoiceId)->first();

        if ($invoiceId) {
            $transaction = Transaction::where('stripe_invoice_id', $invoiceId)->first();
            $customer = User::where('stripe_customer_id', $customerId)->first();
            if ($transaction) {
                $this->updateTransactionStatus($paymentIntent, TransactionStatusEnum::Succeeded);
                Log::info('Transacción creada/actualizada con éxito', ['payment_intent_id' => $paymentIntent->id]);
            } else {
                Transaction::create([
                    'from_type' => get_class($customer), // Valor temporal hasta que se cree el invoice
                    'from_id' => $customer ? $customer->id : null, // Asignar el ID del cliente si está disponible
                    'to_type' => null, // Valor temporal hasta que se cree el invoice
                    'to_id' => null, // Valor temporal hasta que se cree el invoice
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => Transaction::mapStripeStatusToLocal($paymentIntent->status),
                    'date' => now(),
                    'amount_cents' => $paymentIntent->amount,
                    'metadata' => $paymentIntent->toArray(),
                    'payment_id' => $payment ? $payment->id : null,
                    'stripe_payment_id' => $paymentIntent->id,
                    'stripe_invoice_id' => $invoiceId,
                ]);
            }
        }
    }

    protected function handlePaymentIntentCanceled($paymentIntent)
    {
        $invoiceId = $paymentIntent->invoice ?? null;
        $customerId = $paymentIntent->customer;
        $payment = Payment::where('stripe_invoice_id', $invoiceId)->first();

        if ($invoiceId) {
            $transaction = Transaction::where('stripe_invoice_id', $invoiceId)->first();
            $customer = User::where('stripe_customer_id', $customerId)->first();
            if ($transaction) {
                $this->updateTransactionStatus($paymentIntent, TransactionStatusEnum::Succeeded);
                Log::info('Transacción creada/actualizada con éxito', ['payment_intent_id' => $paymentIntent->id]);
            } else {
                Transaction::create([
                    'from_type' => get_class($customer), // Valor temporal hasta que se cree el invoice
                    'from_id' => $customer ? $customer->id : null, // Asignar el ID del cliente si está disponible
                    'to_type' => null, // Valor temporal hasta que se cree el invoice
                    'to_id' => null, // Valor temporal hasta que se cree el invoice
                    'type' => TransactionTypeEnum::Subscription->value,
                    'status' => Transaction::mapStripeStatusToLocal($paymentIntent->status),
                    'date' => now(),
                    'amount_cents' => $paymentIntent->amount,
                    'metadata' => $paymentIntent->toArray(),
                    'payment_id' => $payment ? $payment->id : null,
                    'stripe_payment_id' => $paymentIntent->id,
                    'stripe_invoice_id' => $invoiceId,
                ]);
            }
        }
    }


    protected function updateTransactionStatus($paymentIntent, TransactionStatusEnum $status)
    {
        $invoiceId = $paymentIntent->invoice ?? null;

        if ($invoiceId) {
            // Busca todas las transacciones relacionadas con el invoice
            $transactions = Transaction::where('stripe_invoice_id', $invoiceId)->get();

            if ($transactions->isEmpty()) {
                Log::info('No transactions found for invoice', ['invoice_id' => $invoiceId]);
                return;
            }

            // Actualiza todas las transacciones asociadas
            foreach ($transactions as $transaction) {
                $transaction->update(['status' => $status]);

                Log::info('Transaction updated', [
                    'transaction_id' => $transaction->id,
                    'status' => $status,
                ]);
            }
        } else {
            Log::error('Invoice ID missing from PaymentIntent', ['payment_intent_id' => $paymentIntent->id]);
        }
    }

}