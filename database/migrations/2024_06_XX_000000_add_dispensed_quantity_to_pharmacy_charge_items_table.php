<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDispensedQuantityToPharmacyChargeItemsTable extends Migration
{
    public function up()
    {
        Schema::table('pharmacy_charge_items', function (Blueprint $table) {
            $table->unsignedInteger('dispensed_quantity')->default(0)->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('pharmacy_charge_items', function (Blueprint $table) {
            $table->dropColumn('dispensed_quantity');
        });
    }
}
