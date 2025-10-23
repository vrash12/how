<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNurseRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('nurse_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('patient_id');
            $table->unsignedBigInteger('nurse_id');
            $table->unsignedInteger('doctor_id');
            $table->string('type'); // medication, lab, service, etc.
            $table->json('payload'); // request details
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamps();

            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
            $table->foreign('nurse_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_id')->references('doctor_id')->on('doctors')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('nurse_requests');
    }
}