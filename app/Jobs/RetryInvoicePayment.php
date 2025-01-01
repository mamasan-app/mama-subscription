<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Subscription;
use App\Models\Subscription as LocalSubscription;
use Illuminate\Support\Facades\Log;

class RetryInvoicePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceId;
    protected $gracePeriod;
    protected $attemptCount;
    protected $subscriptionId;

    /**
     * Create a new job instance.
     *
     * @param string $invoiceId
     * @param int $gracePeriod
     * @param int $attemptCount
     * @param string $subscriptionId
     */
    public function __construct($invoiceId, $gracePeriod, $attemptCount, $subscriptionId)
    {
        $this->invoiceId = $invoiceId;
        $this->gracePeriod = $gracePeriod;
        $this->attemptCount = $attemptCount;
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Retrying payment for invoice', [
            'invoice_id' => $this->invoiceId,
            'attempt_count' => $this->attemptCount,
            'grace_period' => $this->gracePeriod,
        ]);

        if ($this->attemptCount < $this->gracePeriod) {
            try {
                // Recuperar la factura desde Stripe
                $invoice = Invoice::retrieve($this->invoiceId);

                // Obtener el PaymentIntent asociado a la factura
                $paymentIntentId = $invoice->payment_intent;

                if ($paymentIntentId) {
                    $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

                    // Confirmar el PaymentIntent para reintentar el pago
                    $paymentIntent->confirm();

                    Log::info('Payment retry successful', ['payment_intent_id' => $paymentIntentId]);
                } else {
                    Log::error('No PaymentIntent found for invoice', ['invoice_id' => $this->invoiceId]);
                }
            } catch (\Exception $e) {
                Log::error('Error retrying payment', [
                    'invoice_id' => $this->invoiceId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Cancelar la suscripción si se superan los intentos permitidos
            try {
                Subscription::update($this->subscriptionId, [
                    'cancel_at_period_end' => true,
                ]);

                $localSubscription = LocalSubscription::where('stripe_subscription_id', $this->subscriptionId)->first();

                if ($localSubscription) {
                    $localSubscription->update([
                        'status' => 'cancelled',
                        'ends_at' => now(),
                    ]);
                }

                Log::info('Subscription cancelled after failed payment attempts', [
                    'subscription_id' => $this->subscriptionId,
                    'invoice_id' => $this->invoiceId,
                ]);
            } catch (\Exception $e) {
                Log::error('Error cancelling subscription', [
                    'subscription_id' => $this->subscriptionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
