<?php

namespace App\Enums;

enum IdentityPrefixEnum: string
{
    case V = 'V';
    case E = 'E';
    case J = 'J';

    public function getLabel(): string
    {
        return match ($this) {
            self::V => 'Venezolano',
            self::E => 'Extranjero',
            self::J => 'Jurídico',
        };
    }
}
