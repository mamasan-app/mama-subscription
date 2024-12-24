<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_payment_id',
        'subscription_id',
        'status',
        'amount_cents',
        'due_date',
        'paid_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Relación con la suscripción.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relación con las transacciones.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Verificar si el pago está vencido.
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && is_null($this->paid_date);
    }

    /**
     * Marcar el pago como completado.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_date' => now(),
        ]);
    }

    /**
     * Calcular el monto en dólares.
     */
    public function getAmountInDollarsAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}

