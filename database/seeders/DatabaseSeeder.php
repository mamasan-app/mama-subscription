<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use App\Models\Address;
use App\Models\Frequency;
use Illuminate\Database\Seeder;
use App\Models\Plan;

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
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $owner = User::create([
            'first_name' => 'owner',
            'last_name' => 'owner',
            'email' => 'store@gmail.com',
            'password' => '201102',
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('owner_store');

        $customer = User::create([
            'first_name' => 'customer',
            'last_name' => 'customer',
            'email' => 'moises.liotawork@gmail.com',
            'password' => '201102',
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');

        $employee = User::create([
            'first_name' => 'employee',
            'last_name' => 'employee',
            'email' => 'employee@gmail.com',
            'password' => '201102',
            'email_verified_at' => now(),
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
        $daily = Frequency::create(['name' => 'Diaria', 'days_count' => 1]);
        $weekly = Frequency::create(['name' => 'Semanal', 'days_count' => 7]);
        $monthly = Frequency::create(['name' => 'Mensual', 'days_count' => 30]);
        $yearly = Frequency::create(['name' => 'Anual', 'days_count' => 365]);

        // Crear planes de suscripción
        Plan::create([
            'name' => 'Plan Critico',
            'description' => 'Acceso limitado a funciones básicas.',
            'price_cents' => 1000, // $50.00
            'published' => true,
            'featured' => false,
            'store_id' => $store->id,
            'frequency_id' => $daily->id,
            'free_days' => 1,
            'grace_period' => 3,
            'infinite_duration' => false,
            'duration' => 6,
        ]);

        Plan::create([
            'name' => 'Plan Básico',
            'description' => 'Acceso limitado a funciones básicas.',
            'price_cents' => 5000, // $50.00
            'published' => true,
            'featured' => false,
            'store_id' => $store->id,
            'frequency_id' => $weekly->id,
            'free_days' => 7,
            'grace_period' => 3,
            'infinite_duration' => false,
            'duration' => 14,
        ]);

        Plan::create([
            'name' => 'Plan Pro',
            'description' => 'Acceso completo a todas las funciones.',
            'price_cents' => 15000, // $150.00
            'published' => true,
            'featured' => true,
            'store_id' => $store->id,
            'frequency_id' => $monthly->id,
            'free_days' => 14,
            'grace_period' => 5,
            'infinite_duration' => true,
        ]);

        Plan::create([
            'name' => 'Plan Premium',
            'description' => 'Acceso completo con soporte prioritario.',
            'price_cents' => 100000, // $1000.00
            'published' => true,
            'featured' => true,
            'store_id' => $store->id,
            'frequency_id' => $yearly->id,
            'free_days' => 30,
            'grace_period' => 10,
            'infinite_duration' => true,
        ]);

    }
}
