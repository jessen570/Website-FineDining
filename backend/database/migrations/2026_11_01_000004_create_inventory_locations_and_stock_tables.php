<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_code')->unique(); // KITCH-JKT, BAR-JKT
            $table->string('name');
            $table->string('type')->default('Internal');
            $table->string('company')->default('Maison Aster');
            $table->timestamps();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->string('location_code');
            $table->string('variant_sku');
            $table->decimal('on_hand', 15, 2)->default(0);
            $table->decimal('reserved', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['location_code', 'variant_sku']);
            $table->foreign('location_code')->references('location_code')->on('locations')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });

        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->string('move_code')->unique();
            $table->string('reference_doc_type'); // Delivery, Receipt, Transfer, Return, Opening
            $table->string('reference_doc_id')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('variant_sku');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('status')->default('Done');
            $table->timestamps();

            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_moves');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('locations');
    }
};
