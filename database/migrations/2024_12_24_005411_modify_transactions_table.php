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
        Schema::table('transactions', function (Blueprint $table) {
            // Eliminar la relación con subscriptions
            $table->dropForeign(['subscription_id']); // Si existe una clave foránea
            $table->dropColumn('subscription_id');

            // Agregar la relación con payments
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Restaurar la relación con subscriptions
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('cascade');

            // Eliminar la relación con payments
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }
};

