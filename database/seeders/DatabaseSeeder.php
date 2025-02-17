<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Frequency;
use App\Models\Plan;
use App\Models\Store;
use App\Models\User;
use App\Enums\PaymentStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

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
            'password' => bcrypt('123456'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $owner = User::create([
            'first_name' => 'owner',
            'last_name' => 'owner',
            'email' => 'mt_jaime@yahoo.com',
            'password' => bcrypt('123456'),
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('owner_store');

        $customer = User::create([
            'first_name' => 'customer',
            'last_name' => 'customer',
            'email' => 'jaime@mamasan.app',
            'identity_document' => 'V-15663644',
            'phone_number' => '04122491919',
            'password' => bcrypt('123456'),
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');

        $employee = User::create([
            'first_name' => 'employee',
            'last_name' => 'employee',
            'email' => 'employee@gmail.com',
            'password' => bcrypt('123456'),
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
            'location' => 'Guarenas, frente al estacionamiento del Seguro Social',
            'store_id' => $store->id,
        ]);

        // Asociar usuarios a la tienda
        $store->users()->attach($owner->id, ['role' => 'owner_store']);
        $store->users()->attach($employee->id, ['role' => 'employee']);
        $store->users()->attach($customer->id, ['role' => 'customer']);
        $store->users()->attach($employee->id, ['role' => 'customer']);

        // Crear cuenta bancaria para el usuario customer y asociarla a la tienda
        BankAccount::create([
            'user_id' => $customer->id,
            'bank_code' => '0105', // Código del banco
            'phone_number' => '04122491919',
            'identity_number' => 'V15663644',
            'default_account' => true, // Se marca como cuenta por defecto
        ]);

        BankAccount::create([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'bank_code' => '0172', // Código del banco
            'phone_number' => '04122491919',
            'identity_number' => 'V15663644',
            'default_account' => true, // Se marca como cuenta por defecto
        ]);

        // Crear frecuencias
        $daily = Frequency::create(['name' => 'Diaria', 'days_count' => 1]);
        $weekly = Frequency::create(['name' => 'Semanal', 'days_count' => 7]);
        $monthly = Frequency::create(['name' => 'Mensual', 'days_count' => 30]);
        $yearly = Frequency::create(['name' => 'Anual', 'days_count' => 365]);

        // Crear planes de suscripción
        Plan::create([
            'name' => 'Plan Critico',
            'description' => 'Acceso limitado a funciones básicas.',
            'price_cents' => 1000, // $10.00
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

        $plan = Plan::create([
            'name' => 'Prueba',
            'description' => 'Acceso completo con soporte prioritario.',
            'price_cents' => 10,
            'published' => true,
            'featured' => true,
            'store_id' => $store->id,
            'frequency_id' => $yearly->id,
            'free_days' => 30,
            'grace_period' => 10,
            'infinite_duration' => true,
        ]);

        // Crear suscripción asociada al Plan
        $subscription = Subscription::create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'service_id' => $plan->id,
            'service_name' => $plan->name,
            'service_description' => $plan->description,
            'service_price_cents' => $plan->price_cents,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(30),
            'renews_at' => now()->addDays(365),
            'ends_at' => null,
            'last_notification_at' => null,
            'expires_at' => now()->addDays(375),
            'frequency_days' => $plan->frequency->days_count,
        ]);

        // Crear Payment **Aprobado** con una **Transaction** aprobada
        $approvedPayment = Payment::create([
            'subscription_id' => $subscription->id,
            'status' => PaymentStatusEnum::Completed,
            'amount_cents' => $subscription->service_price_cents,
            'due_date' => now()->subDays(10),
            'paid_date' => now()->subDays(5),
            'is_bs' => true,
            'paid' => true,
        ]);

        Transaction::create([
            'from_type' => get_class($customer),
            'from_id' => $customer->id,
            'to_type' => get_class($store),
            'to_id' => $store->id,
            'type' => TransactionTypeEnum::Subscription->value,
            'status' => TransactionStatusEnum::Succeeded,
            'date' => now()->subDays(5),
            'amount_cents' => $approvedPayment->amount_cents,
            'metadata' => ['reference' => 'TRANS123456'],
            'payment_id' => $approvedPayment->id,
            'is_bs' => true,
        ]);

        // Crear Payment **Pendiente** para pruebas
        Payment::create([
            'subscription_id' => $subscription->id,
            'status' => PaymentStatusEnum::Pending,
            'amount_cents' => $subscription->service_price_cents,
            'due_date' => now()->addDays(5),
            'is_bs' => true,
            'paid' => false,
        ]);
    }
}
