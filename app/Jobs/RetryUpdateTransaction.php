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
        $invoiceId = $this->paymentIntent->invoice ?? null;

        if ($invoiceId) {
            $payment = Payment::where('stripe_invoice_id', $invoiceId)->first();

            if ($payment) {
                $transactions = Transaction::where('stripe_invoice_id', $invoiceId)->get();

                foreach ($transactions as $transaction) {
                    $transaction->update(['status' => $this->status]);

                    Log::info('Transaction updated (retry)', [
                        'transaction_id' => $transaction->id,
                        'status' => $this->status,
                    ]);
                }
            } else {
                // Si el intento falla nuevamente, vuelve a reintentar hasta un máximo de 5 veces
                if ($this->attempts < 5) {
                    Log::warning('Payment not found, re-scheduling retry', ['invoice_id' => $invoiceId, 'attempt' => $this->attempts]);
                    RetryUpdateTransaction::dispatch($this->paymentIntent, $this->status, $this->attempts + 1)
                        ->delay(now()->addSeconds(30));
                } else {
                    Log::error('Payment not found after maximum retries', ['invoice_id' => $invoiceId]);
                }
            }
        } else {
            Log::error('Invoice ID missing from PaymentIntent on retry', ['payment_intent_id' => $this->paymentIntent->id]);
        }
    }
}
