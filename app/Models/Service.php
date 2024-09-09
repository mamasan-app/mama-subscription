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
        'published',
        'featured',
        'frequency_id',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'price' => 'float',
    ];

    public function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->published,
        );
    }

    protected function formatPrice(float $amount): string
    {
        return number_format($amount, 2) . ' USD';
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function frequency()
    {
        return $this->belongsTo(Frequency::class);
    }
}
