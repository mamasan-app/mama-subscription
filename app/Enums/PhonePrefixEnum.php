<?php

namespace App\Enums;

enum PhonePrefixEnum: string
{
    case PREFIX_0414 = '0414';
    case PREFIX_0424 = '0424';
    case PREFIX_0412 = '0412';
    case PREFIX_0416 = '0416';
    case PREFIX_0426 = '0426';

    public function getLabel(): string
    {
        return match ($this) {
            self::PREFIX_0414 => '0414',
            self::PREFIX_0424 => '0424',
            self::PREFIX_0412 => '0412',
            self::PREFIX_0416 => '0416',
            self::PREFIX_0426 => '0426',
        };
    }
}
