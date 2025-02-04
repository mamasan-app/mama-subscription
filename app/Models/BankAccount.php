<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'bank_accounts';

    /**
     * Los atributos que se pueden asignar de forma masiva.
     */
    protected $fillable = [
        'user_id',
        'store_id', // Nuevo atributo
        'bank_code',
        'phone_number',
        'identity_number',
        'default_account', // Nuevo atributo
    ];

    /**
     * Relación: Una cuenta bancaria pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Una cuenta bancaria puede pertenecer a una tienda.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
