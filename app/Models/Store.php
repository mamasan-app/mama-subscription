<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    protected $table = 'stores';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'url',
        'address',
        'rif_path',
        'certificate_of_incorporation_path',
        'owner_id',
        'verified',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->{$store->getKeyName()})) {
                $store->{$store->getKeyName()} = (string) Str::ulid();
            }
        });
    }


    // Relación con los usuarios (owner_store, employees, customers)
    public function users()
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    // Relación específica con los 'employees'
    public function employees()
    {
        return $this->users()->wherePivot('role', 'employee');
    }

    // Relación específica con los 'customers'
    public function customers()
    {
        return $this->users()->wherePivot('role', 'customer');
    }

    // Relación específica con los 'owner_store'
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

}
