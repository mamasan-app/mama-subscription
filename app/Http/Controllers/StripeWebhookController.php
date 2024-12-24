<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('Stripe-Signature');

        // Verificar la firma del webhook
        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                config('stripe.webhook_secret') // Asegúrate de tener esto en tu .env
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Manejar los eventos específicos
        if ($event->type === 'invoice.payment_succeeded') {
            $this->handlePaymentSucceeded($event->data->object);
        } elseif ($event->type === 'invoice.payment_failed') {
            $this->handlePaymentFailed($event->data->object);
        } elseif ($event->type === 'subscription.updated') {
            $this->handleSubscriptionUpdated($event->data->object);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentSucceeded($invoice)
    {
        // Obtener el ID del Payment asociado desde el metadata
        $paymentId = $invoice->metadata->payment_id ?? null;

        if ($paymentId) {
            $payment = Payment::find($paymentId);

            if ($payment) {
                $payment->update([
                    'status' => 'approved',
                    'paid_date' => Carbon::now(),
                ]);

                // Actualizar transacción asociada
                $transaction = $payment->transactions()->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => 'completed',
                    ]);
                }
            }
        }
    }

    protected function handlePaymentFailed($invoice)
    {
        $paymentId = $invoice->metadata->payment_id ?? null;

        if ($paymentId) {
            $payment = Payment::find($paymentId);

            if ($payment) {
                $payment->update(['status' => 'failed']);
            }
        }
    }

    protected function handleSubscriptionUpdated($subscription)
    {
        // Buscar la suscripción local usando el stripe_subscription_id
        $localSubscription = \App\Models\Subscription::where('stripe_subscription_id', $subscription->id)->first();

        if (!$localSubscription) {
            \Log::warning("No se encontró una suscripción local para el stripe_subscription_id: {$subscription->id}");
            return;
        }

        // Mapear los estados de Stripe a los estados de tu sistema
        $statusMap = [
            'active' => \App\Enums\SubscriptionStatusEnum::Active->value,
            'past_due' => \App\Enums\SubscriptionStatusEnum::PastDue->value,
            'canceled' => \App\Enums\SubscriptionStatusEnum::Cancelled->value,
            'incomplete' => \App\Enums\SubscriptionStatusEnum::Pending->value,
            'incomplete_expired' => \App\Enums\SubscriptionStatusEnum::Expired->value,
            'trialing' => \App\Enums\SubscriptionStatusEnum::OnTrial->value,
            'unpaid' => \App\Enums\SubscriptionStatusEnum::PastDue->value,
        ];

        // Actualizar el estado de la suscripción en tu sistema
        $localSubscription->update([
            'status' => $statusMap[$subscription->status] ?? \App\Enums\SubscriptionStatusEnum::Pending->value,
            'trial_ends_at' => $subscription->trial_end ? \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
            'renews_at' => $subscription->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end) : null,
            'ends_at' => $subscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at) : null,
            'expires_at' => $subscription->cancel_at_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end) : null,
        ]);

        \Log::info("Suscripción actualizada: {$localSubscription->id}", [
            'stripe_subscription_id' => $subscription->id,
            'status' => $subscription->status,
        ]);
    }

}

