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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->morphs('from'); // Relación polimórfica para origen de la transacción
            $table->nullableMorphs('to'); // Relación polimórfica para destino de la transacción
            $table->string('type'); // Tipo de transacción
            $table->string('status'); // Estado de la transacción
            $table->date('date')->nullable(); // Fecha de la transacción
            $table->integer('amount_cents'); // Monto en centavos de dólar
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
