<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Jobs\MonitorTransactionStatus;
use Illuminate\Console\Command;
use App\Enums\TransactionStatusEnum;

class ProcessPendingTransactions extends Command
{
    /**
     * El nombre y la firma del comando (para invocarlo en Artisan).
     *
     * @var string
     */
    protected $signature = 'transactions:process';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Procesa todas las transacciones en estado "Processing"';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        // Obtener todas las transacciones con estado "Processing"
        $transactions = Transaction::where('status', TransactionStatusEnum::Processing)->get();

        if ($transactions->isEmpty()) {
            $this->info('No hay transacciones en estado "Processing".');
            return;
        }

        // Despachar el job para cada transacción
        foreach ($transactions as $transaction) {
            MonitorTransactionStatus::dispatch($transaction->metadata['id']);
            $this->info("Despachado job para la transacción con ID: {$transaction->id}");
        }

        $this->info('Se han despachado todas las transacciones pendientes.');
    }
}
