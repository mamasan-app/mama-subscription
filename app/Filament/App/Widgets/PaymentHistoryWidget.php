<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Payment;

class PaymentHistoryWidget extends BaseWidget
{
    protected function getTableQuery(): Builder
    {
        // Query para obtener los pagos del cliente actual
        return Payment::query()
            ->where('client_id', auth()->id()) // Solo los pagos del cliente autenticado
            ->latest('created_at'); // Ordenados por fecha más reciente
    }

    protected function getTableColumns(): array
    {
        return [
            [
                'label' => 'Fecha',
                'name' => 'created_at',
                'format' => 'datetime',
            ],
            [
                'label' => 'Monto',
                'name' => 'amount_cents',
                'format' => fn($value) => '$' . number_format($value / 100, 2),
            ],
            [
                'label' => 'Estado',
                'name' => 'status',
                'format' => fn($value) => ucfirst($value),
            ],
        ];
    }
}
