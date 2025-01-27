<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('user_id')
                ->constrained('users') // Relación con la tabla 'users'
                ->onDelete('cascade'); // Si se elimina el usuario, se eliminan las cuentas asociadas

            $table->string('bank_code', 4); // Código del banco (4 números)
            $table->string('phone_number', 11); // Número de teléfono (11 números)
            $table->string('identity_number'); // Número de identidad

            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
