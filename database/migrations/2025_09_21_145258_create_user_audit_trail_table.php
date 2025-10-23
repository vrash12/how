<?php
// database/migrations/xxxx_xx_xx_create_user_audit_trail_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_audit_trail', function (Blueprint $table) {
            $table->id('audit_id');
            $table->unsignedBigInteger('user_id'); // Who did the action
            $table->string('username')->nullable(); // Cache username for deleted users
            $table->string('user_role'); // What role they had
            $table->string('action'); // 'create', 'update', 'delete', 'complete', 'charge'
            $table->string('module'); // 'billing', 'pharmacy', 'lab', 'operating_room', 'doctor'
            $table->string('affected_table'); // What table was changed
            $table->unsignedBigInteger('affected_record_id')->nullable(); // ID of changed record
            $table->unsignedBigInteger('patient_id')->nullable(); // Patient affected (if applicable)
            $table->string('patient_name')->nullable(); // Cache patient name
            $table->text('description'); // Human readable description
            $table->json('old_data')->nullable(); // Before changes
            $table->json('new_data')->nullable(); // After changes
            $table->decimal('amount_involved', 10, 2)->nullable(); // Money involved
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index(['patient_id', 'created_at']);
            $table->index(['action', 'module']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_audit_trail');
    }
};