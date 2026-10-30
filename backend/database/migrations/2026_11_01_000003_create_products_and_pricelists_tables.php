<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('variant_sku')->unique(); // FD-001 ... BV-015
            $table->string('name');
            $table->string('size');
            $table->string('color');
            $table->string('unit'); // porsi, botol, gelas, cangkir
            $table->decimal('sales_price', 15, 2);
            $table->decimal('standard_cost', 15, 2);
            $table->string('default_vendor_id'); // V-01, V-02, ...
            $table->timestamps();
        });

        Schema::create('vendor_pricelists', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_id');
            $table->string('variant_sku');
            $table->decimal('unit_purchase_price', 15, 2);
            $table->string('currency')->default('IDR');
            $table->decimal('min_qty', 10, 2)->default(1);
            $table->timestamps();

            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_pricelists');
        Schema::dropIfExists('products');
    }
};

