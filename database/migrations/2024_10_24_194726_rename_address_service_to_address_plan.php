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
        // Renombrar la tabla address_service a address_plan
        Schema::rename('address_service', 'address_plan');

        // Cambiar la columna service_id a plan_id
        Schema::table('address_plan', function (Blueprint $table) {
            // Renombrar la columna service_id a plan_id
            $table->renameColumn('service_id', 'plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Renombrar la tabla de regreso a address_service
        Schema::rename('address_plan', 'address_service');

        // Cambiar de vuelta la columna plan_id a service_id
        Schema::table('address_service', function (Blueprint $table) {
            $table->renameColumn('plan_id', 'service_id');
        });
    }
};
