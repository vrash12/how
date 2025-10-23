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
        Schema::create('notifications_latest', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
              $table->string('type')->nullable();
                $table->string('sendTo_id')->nullable();
                  $table->string('from_name')->nullable();
                    $table->string('read')->nullable();
                      $table->string('message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications_latest');
    }
};
