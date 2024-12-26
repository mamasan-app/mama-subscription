<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentStatusEnum: string implements HasLabel
{
    case Pending = 'pending'; // Estado inicial cuando el Payment está creado.
    case Completed = 'completed'; // El pago fue exitoso.
    case Failed = 'failed'; // El pago falló.
    case Cancelled = 'cancelled'; // La factura fue anulada.
    case Unknown = 'unknown'; // Estado desconocido o no manejado.
    case Uncollectible = 'uncollectible'; // La factura es incobrable.

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Completed => 'Completado',
            self::Failed => 'Fallido',
            self::Cancelled => 'Cancelado',
            self::Unknown => 'Desconocido',
            self::Uncollectible => 'Incobrable',
        };
    }

    public static function fromStripeStatus(string $stripeStatus): self
    {
        return match ($stripeStatus) {
            'paid' => self::Completed,
            'pending_payment', 'unpaid' => self::Pending, // Cambia a un solo caso o usa un array de coincidencias.
            'failed' => self::Failed,
            'void' => self::Cancelled,
            'uncollectible' => self::Uncollectible,
            default => self::Unknown,
        };
    }

}
