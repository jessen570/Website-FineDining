<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // TO/2026/0001 ...
            $table->string('booking_id')->nullable();
            $table->string('table_id');
            $table->string('server_id'); // Employee E-04 etc
            $table->string('customer_id')->nullable();
            $table->string('status')->default('Open'); // Open, Served, Billed, Paid, Cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('pbjt_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->integer('split_count')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('table_id')->references('table_id')->on('restaurant_tables')->onDelete('cascade');
            $table->foreign('server_id')->references('employee_id')->on('employees')->onDelete('restrict');
        });

        Schema::create('table_order_lines', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('variant_sku');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('pbjt_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('status')->default('Ordered'); // Ordered, Served, Invoiced
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('table_orders')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_order_lines');
        Schema::dropIfExists('table_orders');
    }
};
