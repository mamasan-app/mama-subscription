<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Models\BankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba para verificar que el registro de usuario y tienda funciona correctamente.
     *
     * @return void
     */
    public function test_user_and_store_registration_with_bank_account(): void
    {
        // Datos simulados del formulario de registro
        $data = [
            'first_name' => 'Carlos',
            'last_name' => 'Pérez',
            'email' => 'carlos.perez@example.com',
            'phone_number' => '04241234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'identity_prefix' => 'V',
            'identity_number' => '12345678',
            'birth_date' => '1990-01-01',
            'address' => 'Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1',
            'selfie_path' => 'selfie.jpg',
            'ci_picture_path' => 'ci.jpg',
            'terms_and_conditions_accepted' => true,
            // Datos de la tienda
            'store_name' => 'Mi Tienda',
            'store_description' => 'Tienda de prueba',
            'short_address' => 'Altamira',
            'long_address' => 'Av. 2 con Calle 2, Edificio 2, Piso 2, Apartamento 2',
            'store_rif_path' => 'rif.jpg',
            'constitutive_document_path' => 'certificate.jpg',
            // Datos de la cuenta bancaria
            'bank_code' => '0102',
            'phone_prefix' => '0412',
            'bank_phone_number' => '3456789',
            'store_identity_number' => 'V12345678',
        ];

        // Enviar la solicitud de registro simulada
        $response = $this->post(route('filament.auth.register'), $data);

        // Verificar que el usuario fue creado
        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // Verificar que la tienda fue creada y asociada al usuario
        $this->assertDatabaseHas('stores', [
            'name' => $data['store_name'],
            'owner_id' => $user->id,
        ]);

        $store = Store::where('name', $data['store_name'])->first();

        // Verificar que la cuenta bancaria fue creada y asociada a la tienda
        $this->assertDatabaseHas('bank_accounts', [
            'store_id' => $store->id,
            'bank_code' => $data['bank_code'],
            'phone_number' => $data['phone_prefix'] . $data['bank_phone_number'],
            'identity_number' => $data['store_identity_number'],
            'default_account' => true,
        ]);

        // Verificar que el usuario fue redirigido después del registro
        $response->assertRedirect(route('filament.auth.login'));
    }

    /**
     * Prueba para validar que no se puede registrar un usuario con un correo existente.
     *
     * @return void
     */
    public function test_user_cannot_register_with_existing_email(): void
    {
        // Crear un usuario existente
        User::factory()->create([
            'email' => 'carlos.perez@example.com',
        ]);

        // Datos simulados del formulario de registro
        $data = [
            'first_name' => 'Carlos',
            'last_name' => 'Pérez',
            'email' => 'carlos.perez@example.com',
            'phone_number' => '04241234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'identity_prefix' => 'V',
            'identity_number' => '12345678',
            'birth_date' => '1990-01-01',
            'address' => 'Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1',
            'selfie_path' => 'selfie.jpg',
            'ci_picture_path' => 'ci.jpg',
            'terms_and_conditions_accepted' => true,
        ];

        // Enviar la solicitud de registro simulada
        $response = $this->post(route('filament.auth.register'), $data);

        // Verificar que no se creó un nuevo usuario
        $this->assertDatabaseCount('users', 1);

        // Verificar que se muestra un mensaje de error
        $response->assertSessionHasErrors(['email']);
    }
}