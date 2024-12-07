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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Agregar columnas desnormalizadas para datos del servicio (plan)
            $table->string('service_name')->nullable()->after('service_id');
            $table->text('service_description')->nullable()->after('service_name');
            $table->unsignedInteger('service_price_cents')->default(0)->after('service_description');

            // Agregar columnas desnormalizadas para datos de la frecuencia
            $table->string('frequency_name')->nullable()->after('service_price_cents');
            $table->integer('frequency_days')->nullable()->after('frequency_name');

            // Agregar relación opcional con la frecuencia si es necesario
            $table->foreignId('frequency_id')->nullable()->after('service_id')->constrained('frequencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Eliminar las columnas desnormalizadas
            $table->dropColumn([
                'service_name',
                'service_description',
                'service_price_cents',
                'frequency_name',
                'frequency_days',
            ]);

            // Eliminar la relación con frecuencia si se agregó
            $table->dropColumn('frequency_id');
        });
    }
};
