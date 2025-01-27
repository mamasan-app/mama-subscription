<?php

namespace App\Models;

use App\Concerns\HasStore;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasStore;

    /**
     * Relación con los usuarios (empleados).
     *
     * @return BelongsToMany<User>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('role', 'employee')
            ->withTimestamps();
    }
}
