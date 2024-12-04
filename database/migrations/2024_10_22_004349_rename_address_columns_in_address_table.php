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
        Schema::table('address', function (Blueprint $table) {
            // Renombrar las columnas
            $table->renameColumn('short_address', 'branch');
            $table->renameColumn('long_address', 'location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('address', function (Blueprint $table) {
            // Revertir los cambios
            $table->renameColumn('branch', 'short_address');
            $table->renameColumn('location', 'long_address');
        });
    }
};

