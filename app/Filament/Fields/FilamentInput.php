<?php

declare(strict_types=1);

namespace App\Filament\Fields;

use Filament\Forms\Components\Field;

interface FilamentInput
{
    public static function make(string $name): Field;
}
