<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use App\Models\Address;
use App\Models\Frequency;
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

        // Crear usuarios y asignar roles sin contraseñas
        $admin = User::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => '201102',
        ]);
        $admin->assignRole('admin');

        $owner = User::create([
            'first_name' => 'owner',
            'last_name' => 'owner',
            'email' => 'store@gmail.com',
            'password' => '201102',
        ]);
        $owner->assignRole('owner_store');

        $customer = User::create([
            'first_name' => 'customer',
            'last_name' => 'customer',
            'email' => 'customer@gmail.com',
            'password' => '201102',
        ]);
        $customer->assignRole('customer');

        $employee = User::create([
            'first_name' => 'employee',
            'last_name' => 'employee',
            'email' => 'employee@gmail.com',
            'password' => '201102',
        ]);
        $employee->assignRole('employee');
        $employee->assignRole('customer');

        // Crear tienda y dirección asociada
        $store = Store::create([
            'name' => 'Tienda Prueba',
            'slug' => 'tiendaPrueba',
            'owner_id' => $owner->id,
        ]);

        $address = Address::create([
            'branch' => 'Guarenas',
            'location' => 'Guarenas, frenta el estacionamiento del Seguro Social',
            'store_id' => $store->id,
        ]);

        // Asociar usuarios a la tienda
        $store->users()->attach($owner->id, ['role' => 'owner_store']);
        $store->users()->attach($employee->id, ['role' => 'employee']);
        $store->users()->attach($customer->id, ['role' => 'customer']);
        $store->users()->attach($employee->id, ['role' => 'customer']);

        // Crear frecuencias
        Frequency::create(['name' => 'Semanal', 'days_count' => 7]);
        Frequency::create(['name' => 'Quincenal', 'days_count' => 15]);
        Frequency::create(['name' => 'Mensual', 'days_count' => 30]);
        Frequency::create(['name' => 'Trimestral', 'days_count' => 90]);
    }
}
