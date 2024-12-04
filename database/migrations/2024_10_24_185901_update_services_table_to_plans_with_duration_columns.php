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
        // Renombrar la tabla 'services' a 'plans'
        Schema::rename('services', 'plans');

        // Actualizar la nueva tabla 'plans' agregando las columnas solicitadas
        Schema::table('plans', function (Blueprint $table) {
            // Añadir las nuevas columnas
            $table->boolean('infinite_duration')->default(false)->after('featured'); // Campo booleano para definir si es de duración infinita
            $table->unsignedInteger('duration')->nullable()->after('infinite_duration'); // Campo de duración para planes finitos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios
        Schema::table('plans', function (Blueprint $table) {
            // Eliminar las columnas recién agregadas
            $table->dropColumn(['infinite_duration', 'duration']);
        });

        // Renombrar la tabla de vuelta a 'services'
        Schema::rename('plans', 'services');
    }
};

