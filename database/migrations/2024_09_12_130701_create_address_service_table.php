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
        Schema::create('address_service', function (Blueprint $table) {
            $table->id();

            // Clave foránea hacia la tabla 'services'
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');

            // Clave foránea hacia la tabla 'address'
            $table->foreignId('address_id')
                ->constrained('address')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address_service');
    }
};
