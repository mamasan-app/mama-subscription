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
     * @return void
     */
    public function __construct($operationId)
    {
        $this->operationId = $operationId;
    }

    /**
     * Execute the job.
     *
     * @return void
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
            do {
                sleep(10); // Esperar 5 segundos
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
            } while ($statusCode === 'AC00'); // Código de operación en proceso

            // Actualizar transacción y pago según el estado final
            $payment = $transaction->payment;

            if ($statusCode === 'ACCP') {
                // Actualizar transacción y pago a completado
                $transaction->update([
                    'status' => TransactionStatusEnum::Succeeded,
                    'metadata' => $response->json(),
                ]);

                $payment->update([
                    'status' => PaymentStatusEnum::Completed,
                ]);

                Notification::make()
                    ->title('Pago completado')
                    ->body('El pago se procesó exitosamente.')
                    ->success()
                    ->send();
            } else {
                // Manejar fallo en la transacción
                $transaction->update([
                    'status' => TransactionStatusEnum::Failed,
                    'metadata' => $response->json(),
                ]);

                // Si la suscripción está en periodo de prueba
                $subscription = $payment->subscription;
                if ($subscription->isOnTrial) {
                    $payment->update([
                        'status' => PaymentStatusEnum::Pending,
                    ]);
                } else {
                    // Si no está en periodo de prueba, verificar expiración
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
