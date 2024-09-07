<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $this->call(RolePermissionSeeder::class);

        // Crear usuarios y asignar roles
        $admin = User::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $admin->assignRole('admin');

        $owner = User::create([
            'first_name' => 'owner',
            'last_name' => 'owner',
            'email' => 'store@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $owner->assignRole('owner_store');

        $customer = User::create([
            'first_name' => 'customer',
            'last_name' => 'customer',
            'email' => 'customer@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $customer->assignRole('customer');

        $employee = User::create([
            'first_name' => 'employee',
            'last_name' => 'employee',
            'email' => 'employee@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $employee->assignRole('employee');

        $store = Store::create([
            'name' => 'Tienda Prueba',
            'address' => 'Guarenas',
            'owner_id' => $owner->id,
        ]);

        
        // Asociar el owner_store a la tienda
        $store->users()->attach($owner->id, ['role' => 'owner_store']);

        // Asociar el employee a la tienda
        $store->users()->attach($employee->id, ['role' => 'employee']);
        
        // Si quieres, también puedes asociar clientes
        $store->users()->attach($customer->id, ['role' => 'customer']);

    }
}
