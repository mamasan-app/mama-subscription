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
        Schema::table('services', function (Blueprint $table) {
            // Agregar las nuevas columnas
            $table->unsignedInteger('free_days')->default(0)->after('featured');
            $table->unsignedInteger('grace_period')->default(0)->after('free_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Eliminar las columnas en la reversión
            $table->dropColumn('free_days');
            $table->dropColumn('grace_period');
        });
    }
};
