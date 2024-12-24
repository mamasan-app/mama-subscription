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
        'payment_id',
        'stripe_payment_id', // Nuevo campo agregado
    ];

    protected $casts = [
        'type' => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
        'date' => 'date',
        'metadata' => 'array',
    ];

    public function from(): MorphTo
    {
        return $this->morphTo();
    }

    public function to(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Relación con Payment.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Crear una transacción desde un Payment Intent.
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @param Payment $payment
     * @return static
     */
    public static function createFromPaymentIntent($paymentIntent, Payment $payment): self
    {
        return self::create([
            'from_type' => get_class($payment->subscription->user),
            'from_id' => $payment->subscription->user->id,
            'to_type' => get_class($payment->subscription->service->store),
            'to_id' => $payment->subscription->service->store->id,
            'type' => TransactionTypeEnum::Subscription->value,
            'status' => self::mapStripeStatusToLocal($paymentIntent->status),
            'date' => now(),
            'amount_cents' => $paymentIntent->amount,
            'metadata' => $paymentIntent->toArray(),
            'payment_id' => $payment->id,
            'stripe_payment_id' => $paymentIntent->id,
        ]);
    }

    /**
     * Mapear el estado de Stripe a un estado local.
     *
     * @param string $stripeStatus
     * @return string
     */
    protected static function mapStripeStatusToLocal(string $stripeStatus): TransactionStatusEnum
    {
        return match ($stripeStatus) {
            'requires_payment_method' => TransactionStatusEnum::RequiresPaymentMethod,
            'requires_confirmation' => TransactionStatusEnum::RequiresConfirmation,
            'requires_action' => TransactionStatusEnum::RequiresAction,
            'processing' => TransactionStatusEnum::Processing,
            'succeeded' => TransactionStatusEnum::Succeeded,
            'canceled' => TransactionStatusEnum::Canceled,
            'failed' => TransactionStatusEnum::Failed,
            default => TransactionStatusEnum::Failed, // O un valor predeterminado que prefieras
        };
    }

}
