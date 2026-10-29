<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_id')->unique(); // TB-01 .. TB-06
            $table->string('floor'); // Main Dining, Private Room, Window
            $table->integer('seats'); // 2, 4, 6
            $table->string('status')->default('available'); // available, occupied, reserved
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id')->unique(); // BK0001 ...
            $table->string('customer_id');
            $table->string('table_id');
            $table->integer('party_size');
            $table->date('booking_date');
            $table->dateTime('start_time');
            $table->dateTime('end_time'); // 120 minutes service
            $table->dateTime('buffer_end_time'); // 120m + 15m cleaning buffer
            $table->string('status')->default('Confirmed'); // Draft, Confirmed, Checked-In, Done, Cancelled
            $table->string('assigned_server_id')->nullable(); // Employee ID
            $table->boolean('is_vip')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('table_id')->references('table_id')->on('restaurant_tables')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('restaurant_tables');
    }
};
