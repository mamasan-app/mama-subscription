<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Asegúrate de ejecutar el seeder de roles antes
        $this->call(RoleSeeder::class);

        // Crear usuarios y asignar roles
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $admin->assignRole('admin');

        $owner = User::create([
            'first_name' => 'Owner',
            'last_name' => 'Owner',
            'email' => 'store@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $owner->assignRole('owner_store');

        $customer = User::create([
            'first_name' => 'Customer',
            'last_name' => 'Customer',
            'email' => 'customer@gmail.com',
            'password' => bcrypt('201102'),
        ]);
        $customer->assignRole('customer');
    }
}
