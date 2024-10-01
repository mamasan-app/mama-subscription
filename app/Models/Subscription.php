<?php

namespace App\Models;

use App\Enums\SubscriptionStatusEnum;
use App\Support\MoneyFormatter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Money\Money;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'trial_ends_at',
        'renews_at',
        'ends_at',
        'last_notification_at',
        'metadata',
        'store_id',
        'service_id',
        'store_id',
        'user_id',
        'expires_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => SubscriptionStatusEnum::class,
        'trial_ends_at' => 'datetime',
        'renews_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_notification_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::Active
        );
    }

    public function isOnTrial(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::OnTrial
        );
    }

    public function isPastDue(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::PastDue
        );
    }

    public function isCancelled(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::Cancelled
        );
    }

    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === SubscriptionStatusEnum::Expired
        );
    }
    public function nextRenewalDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $renewalDate = $this->status === SubscriptionStatusEnum::OnTrial ? $this->trial_ends_at : $this->renews_at;

                // Asumimos que la frecuencia de pago es mensual si no se indica lo contrario
                return match ($this->service->payment_frecuency) {
                    'annual' => $renewalDate->addYear(),
                    'every_6_months' => $renewalDate->addMonths(6),
                    default => $renewalDate->addMonth(),
                };
            }
        );
    }

    public function hasEnded(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ends_at->greaterThan(now())
        );
    }

    public function wasNotifiedRecently(): Attribute
    {
        return Attribute::make(
            get: fn() => abs(now()->diffInHours($this->last_notification_at)) <= 48
        );
    }

    public function canBePaid(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->is_on_trial && !$this->is_expired && !$this->is_cancelled
        );
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPrice(): Money
    {
        if ($this->service && $this->service->price_cents) {
            return Money::USD($this->service->price_cents);
        }

        // Si no se encuentra el precio del servicio, devolver un valor predeterminado o lanzar una excepción
        return Money::USD(0); // Precio predeterminado (por ejemplo, $0.00)
    }

    public function formattedPrice(): string
    {
        return MoneyFormatter::make($this->getPrice())->format();
    }

}
