<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentTypeEnum: string implements HasLabel
{
    case Stripe = 'stripe';
    case DirectDebit = 'direct_debit';  // Domiciliación bancaria

    public function getLabel(): string
    {
        return match ($this) {
            PaymentTypeEnum::Stripe => 'Stripe',  // Etiqueta para Stripe
            PaymentTypeEnum::DirectDebit => 'Domiciliación bancaria',  // Etiqueta para domiciliación
        };
    }

    public function isBs(): bool
    {
        return in_array($this, [self::DirectDebit]);
    }

    public function isUsd(): bool
    {
        return in_array($this, [self::Stripe]);  // Stripe
    }

    public function currency(): string
    {
        return match ($this) {
            self::DirectDebit => 'VES',  // Bolívares
            self::Stripe => 'USD',  // Dólares para Stripe
        };
    }

    public function formattedCurrency(): string
    {
        return match ($this) {
            self::DirectDebit => 'Bs.',
            self::Stripe => '$',
        };
    }
}
