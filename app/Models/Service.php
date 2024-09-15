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
        'name',
        'description',
        'price_cents', // Esto está en la base de datos
        'published',
        'featured',
        'frequency_id',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'price_cents' => 'integer',
    ];

    protected $appends = ['price']; // No necesitas price_cents aquí

    /**
     * @return Attribute<Closure, Closure>
     */
    public function price(): Attribute
    {
        return Attribute::make(
            get: fn(): float => $this->price_cents / 100,
            set: fn(float $value): array => [
                'price_cents' => $value * 100,
            ]
        );
    }

    /**
     * @return Attribute<Closure, Closure>
     */
    public function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn(): string => number_format($this->price, 2),
        );
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2) . ' USD';  // Formatea el precio con dos decimales y "USD"
    }

    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->published,
        );
    }

    public function frequency()
    {
        return $this->belongsTo(Frequency::class);
    }

    // Relación muchos a muchos con 'Address'
    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'address_service', 'service_id', 'address_id');
    }


    // Relación con 'Store' a través de 'Address'
    public function store()
    {
        return $this->hasOneThrough(Store::class, Address::class, 'id', 'id', 'id', 'store_id');
    }
}
