<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {


    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_payment_id')->nullable()->after('id')->comment('ID del payment de Stripe');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_id');
        });
    }

};
