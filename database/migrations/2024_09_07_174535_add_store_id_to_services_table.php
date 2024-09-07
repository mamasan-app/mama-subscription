<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            // Agregar columna store_id como clave foránea
            $table->foreignUlid('store_id')->nullable()->constrained('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            // Eliminar la columna store_id si se deshace la migración
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
