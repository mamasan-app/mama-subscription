<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address';

    protected $fillable = ['short_address', 'long_address', 'store_id'];

    /**
     * Relación "Address pertenece a Store".
     * Muchas direcciones (Address) pueden pertenecer a una tienda (Store).
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    // Relación muchos a muchos con 'Service'
    public function services()
    {
        return $this->belongsToMany(Service::class, 'address_service', 'address_id', 'service_id');
    }
}
