<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionStatusEnum: string implements HasColor, HasLabel
{
    case RequiresPaymentMethod = 'requires_payment_method';
    case RequiresConfirmation = 'requires_confirmation';
    case RequiresAction = 'requires_action';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Canceled = 'canceled';
    case Failed = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RequiresPaymentMethod => 'Requiere método de pago',
            self::RequiresConfirmation => 'Requiere confirmación',
            self::RequiresAction => 'Requiere acción',
            self::Processing => 'Procesando',
            self::Succeeded => 'Completada',
            self::Canceled => 'Cancelada',
            self::Failed => 'Fallida',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Succeeded => 'success',
            self::Processing => 'info',
            self::Canceled => 'danger',
            self::Failed => 'danger',
            self::RequiresPaymentMethod, self::RequiresConfirmation, self::RequiresAction => 'warning',
        };
    }
}
