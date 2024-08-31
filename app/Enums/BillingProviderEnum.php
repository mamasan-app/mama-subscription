<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BillingProviderEnum: string implements HasLabel
{
    case Manual = 'Manual';

    case Stripe = 'Stripe';

    public function getLabel(): ?string
    {
        return match ($this) {
            BillingProviderEnum::Manual => 'Métodos Nacionales',
            BillingProviderEnum::Stripe => 'Internacional',
        };
    }
}
