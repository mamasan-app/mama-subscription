<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'type',
        'status',
        'date',
        'amount_cents',
        'metadata',
    ];

    protected $casts = [
        'type' => TransactionTypeEnum::class,  // Enum para tipos de transacción
        'status' => TransactionStatusEnum::class,  // Enum para estatus
        'date' => 'date',
        'metadata' => 'array',
    ];

    public function from(): MorphTo
    {
        return $this->morphTo();  // Relación polimórfica para el origen (puede ser Store o User)
    }

    public function to(): MorphTo
    {
        return $this->morphTo();  // Relación polimórfica para el destino
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;  // Convertir de centavos a dólares
    }

}
