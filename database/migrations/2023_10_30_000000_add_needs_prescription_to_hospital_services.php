<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('hospital_services', 'needs_prescription')) {
            Schema::table('hospital_services', function (Blueprint $table) {
                $table->boolean('needs_prescription')->default(true)->after('service_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('hospital_services', 'needs_prescription')) {
            Schema::table('hospital_services', function (Blueprint $table) {
                $table->dropColumn('needs_prescription');
            });
        }
    }
};
