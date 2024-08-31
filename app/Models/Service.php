<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Cache;


class Service extends Model
{
    use HasFactory;
    protected $table = 'services';
    protected $fillable = [
        'store_id',
        'name',
        'description',
        'price',
        'variant',
        'published',
        'featured',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'price' => 'float',
    ];

    public function monthlyVariant(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getVariantByPaymentFrequency('monthly'),
        );
    }

    public function annualVariant(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getVariantByPaymentFrequency('annual'),
        );
    }

    public function features(): Attribute
    {
        return Attribute::make(
            get: fn() => match ((string) $this->id) {
                config('store.services.basic.id') => config('store.services.basic.features'),
                config('store.services.premium.id') => config('store.services.premium.features'),
                default => config('store.services.standard.features'),
            }
        );
    }

    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->published,
        );
    }

    public function variantByBillingUnit(string $billingUnit): array
    {
        return $this->getVariantByPaymentFrequency($billingUnit);
    }

    protected function getVariantByPaymentFrequency(string $frequency)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $frequency,
            'price' => $this->price,
            'formatted_price' => $this->formatPrice($this->price),
        ];
    }

    protected function formatPrice(float $amount): string
    {
        // Aquí podrías usar una clase de formateo de dinero si la tienes
        return number_format($amount, 2) . ' USD'; // Como ejemplo, formato simple
    }

    public function getRows(): array
    {
        return Cache::rememberForever('services', function () {
            // Aquí puedes personalizar cómo recuperarás y organizarás los servicios.
            return $this->all()->map(function (Service $service) {
                $sort = match ($service->id) {
                    config('store.services.basic.id') => config('store.services.basic.sort'),
                    config('store.services.premium.id') => config('store.services.premium.sort'),
                    default => config('store.services.standard.sort'),
                };

                return [
                    'id' => $service->id,
                    'sort' => $sort,
                    'name' => $service->name,
                    'description' => $service->description,
                    'variants' => [
                        $service->monthlyVariant,
                        $service->annualVariant,
                    ],
                ];
            })
                ->sortBy('sort')
                ->values()
                ->toArray();
        });
    }
}
