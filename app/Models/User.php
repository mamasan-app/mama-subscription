<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'identity_document',
        'email',
        'password',
        'birth_date',
        'address',
        'selfie_path',
        'ci_picture_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->first_name . ' ' . $this->last_name,
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();

        //dd($panelId, auth()->user()->roles->pluck('name'));

        // Lógica para el panel de admin
        if ($panelId === 'admin') {
            return $this->hasRole('admin');
        }

        // Lógica para el panel de app
        if ($panelId === 'app') {
            return $this->hasRole('customer');
        }

        // Lógica para el panel de owner_store
        if ($panelId === 'store') {
            return $this->hasRole('owner_store') || $this->hasRole('employee');
        }

        // Retorna false por defecto si no coincide con ninguno de los paneles
        return false;
    }

    // Relación con las tiendas donde el usuario es 'owner', 'employee' o 'customer'
     public function stores()
     {
         return $this->belongsToMany(Store::class, 'store_user')
                     ->withPivot('role')
                     ->withTimestamps();
     }
 
     // Obtener tiendas donde el usuario es 'employee'
     public function employeeStores()
     {
         return $this->stores()->wherePivot('role', 'employee');
     }
 
     // Obtener tiendas donde el usuario es 'customer'
     public function customerStores()
     {
         return $this->stores()->wherePivot('role', 'customer');
     }
 
     // Obtener tiendas donde el usuario es 'owner_store'
     public function ownedStores()
     {
         return $this->stores()->wherePivot('role', 'owner_store');
     }

}
