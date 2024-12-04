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
        Schema::table('frequencies', function (Blueprint $table) {
            // Renombrar columnas
            $table->renameColumn('nombre', 'name');
            $table->renameColumn('cantidad_dias', 'days_count');
            $table->renameColumn('activo', 'active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frequencies', function (Blueprint $table) {
            // Revertir los cambios de nombre
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('days_count', 'cantidad_dias');
            $table->renameColumn('active', 'activo');
        });
    }
};
