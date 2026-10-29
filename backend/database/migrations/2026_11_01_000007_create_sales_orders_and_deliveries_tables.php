<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // S00007, S00008 ...
            $table->string('customer_id');
            $table->string('status')->default('Draft'); // Draft, Confirmed, Done, Cancelled
            $table->string('payment_terms');
            $table->date('order_date');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->string('approval_id')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('pbjt_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->string('delivery_status')->default('Pending'); // Pending, Partially Delivered, Delivered
            $table->string('invoice_status')->default('To Invoice'); // To Invoice, Partially Invoiced, Invoiced
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('variant_sku');
            $table->decimal('ordered_qty', 10, 2);
            $table->decimal('delivered_qty', 10, 2)->default(0);
            $table->decimal('invoiced_qty', 10, 2)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('pbjt_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id')->unique(); // WH/OUT/0008 ...
            $table->string('sales_order_id');
            $table->string('status')->default('Ready'); // Draft, Ready, Done, Cancelled
            $table->date('delivery_date');
            $table->decimal('cost_of_goods_sold', 15, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->string('backorder_delivery_id')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('order_id')->on('sales_orders')->onDelete('cascade');
        });

        Schema::create('delivery_order_lines', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id');
            $table->string('variant_sku');
            $table->decimal('ordered_qty', 10, 2);
            $table->decimal('delivered_qty', 10, 2);
            $table->decimal('backorder_qty', 10, 2)->default(0);
            $table->decimal('standard_cost', 15, 2);
            $table->decimal('subtotal_cogs', 15, 2);
            $table->timestamps();

            $table->foreign('delivery_id')->references('delivery_id')->on('delivery_orders')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_lines');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
