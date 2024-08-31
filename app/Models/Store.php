<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $table = 'stores';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'description',
        'rif_path',
        'certificate_of_incorporation_path',
    ];
    
}
