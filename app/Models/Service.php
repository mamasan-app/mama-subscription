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

    protected $primaryKey = 'id';  // Ya está configurado correctamente
    public $incrementing = true;  // Si usas IDs autoincrementales, debe estar en true
    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
        'price_cents',
        'published',
        'featured',
        'frequency_id',
        'store_id',
        'free_days',
        'grace_period',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'price_cents' => 'integer',
    ];

    protected $appends = ['price']; // Esto crea un campo virtual 'price' en las salidas JSON

    /**
     * Atributo calculado para el precio en formato decimal.
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
     * Atributo para formatear el precio como un string con dos decimales.
     */
    public function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn(): string => number_format($this->price, 2),
        );
    }

    /**
     * Método para obtener el precio formateado con la moneda.
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 2) . ' USD';  // Devuelve el precio con dos decimales y la etiqueta "USD"
    }

    /**
     * Atributo para verificar si el servicio está publicado.
     */
    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->published,
        );
    }

    /**
     * Relación de muchos a uno con 'Frequency'.
     */
    public function frequency()
    {
        return $this->belongsTo(Frequency::class);
    }

    /**
     * Relación muchos a muchos con 'Address'.
     */
    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'address_service', 'service_id', 'address_id');
    }

    /**
     * Relación de uno a muchos con 'Store'.
     * 
     * Relación directa con la tabla 'stores' usando 'store_id'.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
