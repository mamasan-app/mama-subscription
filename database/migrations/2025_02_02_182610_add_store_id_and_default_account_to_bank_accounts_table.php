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
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Añadir columna store_id como nullable
            $table->foreignId('store_id')
                ->nullable() // Permitir valores null
                ->constrained('stores') // Relación con la tabla stores
                ->nullOnDelete(); // Si se elimina la tienda, la columna se establece como null

            // Añadir columna default_account con valor predeterminado 'False'
            $table->boolean('default_account')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Eliminar las columnas añadidas
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
            $table->dropColumn('default_account');
        });
    }
};
