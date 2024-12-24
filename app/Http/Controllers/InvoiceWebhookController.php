<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Subscription;

class InvoiceWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_invoice');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'invoice.created':
                $this->handleInvoiceCreated($event->data->object);
                break;
            case 'invoice.updated':
                $this->handleInvoiceUpdated($event->data->object);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
            case 'invoice.upcoming':
                $this->handleInvoiceUpcoming($event->data->object);
                break;
            case 'invoice.overdue':
                $this->handleInvoiceOverdue($event->data->object);
                break;
            case 'invoice.voided':
                $this->handleInvoiceVoided($event->data->object);
                break;
            default:
                Log::info("Unhandled event type: {$event->type}");
        }

        return response('Webhook handled', 200);
    }

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

        $subscriptionId = $invoice->subscription;
        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();

        if ($subscription) {
            Payment::create([
                'stripe_invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'status' => 'pending',
                'amount_cents' => $invoice->amount_due,
                'due_date' => isset($invoice->due_date) ? now()->setTimestamp($invoice->due_date) : null,
            ]);
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
            $payment->update([
                'status' => 'completed',
                'paid_date' => now(),
            ]);
        }
    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        Log::error('Invoice payment failed', ['invoice' => $invoice]);

        $payment = Payment::where('stripe_invoice_id', $invoice->id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
            ]);
        }
    }
}
