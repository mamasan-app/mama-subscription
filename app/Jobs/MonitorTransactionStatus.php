<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

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
            do {
                sleep(5); // Esperar 5 segundos antes de consultar nuevamente
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
            } while ($statusCode === 'AC00');

            // Notificar al usuario según el estado final
            if ($statusCode === 'ACCP') {
                Notification::make()
                    ->title('Pago completado')
                    ->body('El pago se procesó exitosamente.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Pago fallido')
                    ->body('No se pudo completar el pago. Inténtelo nuevamente.')
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
