<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Transaction;

class PaymentIntentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;
            case 'payment_intent.canceled':
                $this->handlePaymentIntentCanceled($event->data->object);
                break;
            case 'payment_intent.created':
                $this->handlePaymentIntentCreated($event->data->object);
                break;
            case 'payment_intent.processing':
                $this->handlePaymentIntentProcessing($event->data->object);
                break;
            default:
                Log::info("Unhandled event type: {$event->type}");
        }

        return response('Webhook handled', 200);
    }

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
