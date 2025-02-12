<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction;
    protected $montoVuelto;
    protected $store;

    public function __construct(Transaction $transaction, float $montoVuelto, Store $store)
    {
        $this->transaction = $transaction;
        $this->montoVuelto = $montoVuelto;
        $this->store = $store;
    }

    public function handle()
    {
        if (!$this->store->defaultBankAccount()) {
            return;
        }

        $bankAccount = $this->store->defaultBankAccount();

        $bank = (string) $bankAccount->bank_code;
        $amount = (string) number_format((float) $this->montoVuelto, 2, '.', ''); // Convertir a string con dos decimales
        $phone = (string) $bankAccount->phone_number;
        $identity = (string) $bankAccount->identity_number;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => hash_hmac(
                'sha256',
                $phone . $amount . $bank . $identity,
                config('banking.commerce_id')
            ),
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.vuelto_url'), [
                    'TelefonoDestino' => $phone,
                    'Cedula' => $identity,
                    'Banco' => $bank,
                    'Monto' => $amount,
                    'Concepto' => 'Vuelto',
                    'Ip' => request()->ip(),
                ]);


        Transaction::create([
            'from_type' => Transaction::class,
            'from_id' => $this->transaction->id,
            'to_type' => Store::class,
            'to_id' => $this->store->id,
            'type' => 'refund',
            'status' => 'succeeded',
            'amount_cents' => $this->montoVuelto * 100,
            'metadata' => $response->json(),
            'is_bs' => true,
        ]);

    }
}
