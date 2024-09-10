<?php

namespace App\Models;

use App\Enums\PaymentTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'type',
        'metadata',
        'enabled',
    ];

    protected $casts = [
        'type' => PaymentTypeEnum::class,  // Enum para tipos de pago
        'metadata' => 'array',
        'enabled' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);  // Relación con la tienda
    }
}
