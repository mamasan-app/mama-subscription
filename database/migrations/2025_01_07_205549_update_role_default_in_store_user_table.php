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
        Schema::table('store_user', function (Blueprint $table) {
            // Establecer un valor predeterminado para el campo 'role'
            $table->string('role')->default('employee')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_user', function (Blueprint $table) {
            // Revertir el valor predeterminado del campo 'role'
            $table->string('role')->default(null)->change();
        });
    }
};
