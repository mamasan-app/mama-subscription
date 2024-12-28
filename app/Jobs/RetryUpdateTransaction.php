<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\Payment;
use App\Enums\TransactionStatusEnum;

class RetryUpdateTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $paymentIntent;
    public $status;
    public $attempts;

    public $tries = 5;


    /**
     * Create a new job instance.
     *
     * @param object $paymentIntent
     * @param TransactionStatusEnum $status
     * @param int $attempts
     */
    public function __construct($paymentIntent, TransactionStatusEnum $status, $attempts = 1)
    {
        $this->paymentIntent = $paymentIntent;
        $this->status = $status;
        $this->attempts = $attempts;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $invoiceId = $this->paymentIntent->invoice ?? null;

            if (!$invoiceId) {
                Log::error('Invoice ID missing from PaymentIntent on retry', [
                    'payment_intent_id' => $this->paymentIntent->id,
                ]);
                return;
            }

            $payment = Payment::where('stripe_invoice_id', $invoiceId)->first();

            if (!$payment) {
                Log::warning('Payment not found, re-scheduling retry', [
                    'invoice_id' => $invoiceId,
                ]);

                RetryUpdateTransaction::dispatch($this->paymentIntent, $this->status)
                    ->delay(now()->addSeconds(30));
                return;
            }

            // Actualizar transacciones en bulk
            $updated = Transaction::where('stripe_invoice_id', $invoiceId)
                ->where('status', '!=', $this->status->value)
                ->update(['status' => $this->status]);

            if ($updated) {
                Log::info('Transactions updated (bulk)', [
                    'invoice_id' => $invoiceId,
                    'new_status' => $this->status,
                ]);
            } else {
                Log::info('No transactions required update', [
                    'invoice_id' => $invoiceId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during transaction update', [
                'message' => $e->getMessage(),
                'payment_intent_id' => $this->paymentIntent->id,
            ]);
            throw $e;
        }
    }

}
