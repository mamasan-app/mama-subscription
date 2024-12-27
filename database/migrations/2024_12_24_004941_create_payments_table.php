<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade'); // Relación con suscripciones
            $table->string('status'); // pendiente, completado, fallido
            $table->integer('amount_cents'); // monto en centavos de dólar
            $table->date('due_date')->nullable(); // Fecha de vencimiento
            $table->date('paid_date')->nullable(); // Fecha de pago
            $table->timestamps(); // Incluye created_at (fecha de creación) y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

