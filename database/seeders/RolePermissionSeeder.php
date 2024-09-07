<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear los permisos específicos
        Permission::create(['name' => 'create services']);
        Permission::create(['name' => 'delete services']);
        Permission::create(['name' => 'edit services']);

        // Asignar permisos al rol 'owner_store'
        $ownerRole = Role::firstOrCreate(['name' => 'owner_store']);
        $ownerRole->givePermissionTo(['create services', 'delete services', 'edit services']);

        // Asignar permisos al rol 'employee', solo permisos de edición
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->givePermissionTo(['edit services']);
    }
}
