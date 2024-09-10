<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionStatusEnum: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Declined = 'declined';

    case Returned = 'returned';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Approved => 'Aprobada',
            self::Declined => 'Rechazada',
            self::Returned => 'Devuelta',
            default => 'Pendiente',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Approved => 'success',
            self::Declined => 'danger',
            self::Returned => 'info', // o 'secondary'
            default => 'warning',
        };
    }
}
