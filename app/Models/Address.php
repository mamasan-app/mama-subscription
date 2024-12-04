<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address';

    protected $fillable = ['branch', 'location', 'store_id'];

    /**
     * Relación "Address pertenece a Store".
     * Muchas direcciones (Address) pueden pertenecer a una tienda (Store).
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    // Relación muchos a muchos con 'membership'
    public function memberships()
    {
        return $this->belongsToMany(Plan::class, 'address_plan', 'address_id', 'plan_id');
    }
}
