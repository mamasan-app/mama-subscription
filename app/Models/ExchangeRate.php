<?php

namespace App\Models;

use App\Enums\CurrencyEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'currency',
        'rate',
        'date',
    ];

    protected $casts = [
        'currency' => CurrencyEnum::class,  // Enum para tipos de moneda
        'rate' => 'decimal:6',
    ];

    public function convertToVE(int $amountUsdCents): string
    {
        $totalUsd = $amountUsdCents / 100;

        return number_format($totalUsd * $this->rate, 2);  // Conversión de USD a VE usando la tasa de cambio
    }
}
