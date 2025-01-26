<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use App\Enums\TransactionStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;

class MonitorTransactionStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operationId;

    /**
     * Create a new job instance.
     *
     * @param string $operationId
     */
    public function __construct($operationId)
    {
        $this->operationId = $operationId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Obtener la transacción asociada al operationId
            $transaction = Transaction::where('metadata->id', $this->operationId)
                ->where('is_bs', true)
                ->first();

            if (!$transaction) {
                throw new \Exception("Transacción no encontrada para el ID de operación: {$this->operationId}");
            }

            // Consultar estado de la operación
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => hash_hmac(
                    'sha256',
                    $this->operationId,
                    config('banking.commerce_id')
                ),
                'Commerce' => config('banking.commerce_id'),
            ])->post(config('banking.consult_debit'), [
                        'Id' => $this->operationId,
                    ]);

            $statusCode = $response->json()['code'] ?? null;

            if ($statusCode === 'AC00') {
                // Transacción en proceso: no hacer nada
                return;
            }

            // Procesar el estado final de la transacción
            $payment = $transaction->payment;
            $subscription = $payment->subscription;

            if ($statusCode === 'ACCP') {
                $transaction->update([
                    'status' => TransactionStatusEnum::Succeeded,
                    'metadata' => $response->json(),
                ]);

                $payment->update([
                    'status' => PaymentStatusEnum::Completed,
                ]);

                $subscription->update([
                    'status' => SubscriptionStatusEnum::Active,
                ]);

                Notification::make()
                    ->title('Pago completado')
                    ->body('El pago se procesó exitosamente.')
                    ->success()
                    ->send();
            } else {
                $transaction->update([
                    'status' => TransactionStatusEnum::Failed,
                    'metadata' => $response->json(),
                ]);

                if (!$subscription->isOnTrial) {
                    if (now()->greaterThanOrEqualTo($subscription->expires_at)) {
                        $subscription->update([
                            'status' => SubscriptionStatusEnum::Cancelled,
                        ]);

                        $payment->update([
                            'status' => PaymentStatusEnum::Failed,
                        ]);
                    }
                }

                Notification::make()
                    ->title('Pago fallido')
                    ->body('No se pudo completar el pago. Verifica tu suscripción o intenta nuevamente.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error en la verificación de la transacción')
                ->body('Detalles: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
