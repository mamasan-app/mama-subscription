<?php

namespace Database\Seeders;

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
        // Crear los permisos específicos para servicios
        Permission::create(['name' => 'create plan']);
        Permission::create(['name' => 'delete plan']);
        Permission::create(['name' => 'edit plan']);

        // Crear permisos específicos para suscripciones
        Permission::create(['name' => 'create subscriptions']);
        Permission::create(['name' => 'edit subscriptions']);
        Permission::create(['name' => 'delete subscriptions']);
        Permission::create(['name' => 'view subscriptions']);
        Permission::create(['name' => 'restore subscriptions']);
        Permission::create(['name' => 'force delete subscriptions']);

        // Asignar permisos al rol 'owner_store'
        $ownerRole = Role::firstOrCreate(['name' => 'owner_store']);
        $ownerRole->givePermissionTo([
            'create plan',
            'delete plan',
            'edit plan',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
        ]);

        $ownerRole->givePermissionTo([
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
            'view subscriptions',
            'restore subscriptions',
            'force delete subscriptions',
        ]);

        // Asignar permisos al rol 'employee', solo edición de servicios y suscripciones
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->givePermissionTo([
            'edit plan',
            'create subscriptions',
            'edit subscriptions',
        ]);

        $employeeRole->givePermissionTo([
            'create subscriptions',
            'edit subscriptions',
            'view subscriptions',
        ]);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $customerRole->givePermissionTo([
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
        ]);
    }
}
