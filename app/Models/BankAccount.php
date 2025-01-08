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
        'bank_code',
        'phone_number',
        'identity_number',
    ];

    /**
     * Relación: Una cuenta bancaria pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}