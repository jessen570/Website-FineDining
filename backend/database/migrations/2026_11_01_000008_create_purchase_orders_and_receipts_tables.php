<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_id')->unique(); // PO00007, PO00008 ...
            $table->string('vendor_id');
            $table->string('status')->default('Draft'); // RFQ, Draft, Confirmed, Done, Cancelled
            $table->string('payment_terms');
            $table->date('order_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->string('receipt_status')->default('Pending');
            $table->string('billing_status')->default('Nothing to Bill');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->string('po_id');
            $table->string('variant_sku');
            $table->decimal('ordered_qty', 10, 2);
            $table->decimal('received_qty', 10, 2)->default(0);
            $table->decimal('accepted_qty', 10, 2)->default(0);
            $table->decimal('billed_qty', 10, 2)->default(0);
            $table->decimal('unit_purchase_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_id')->unique(); // WH/IN/0008 ...
            $table->string('purchase_order_id');
            $table->string('status')->default('Draft'); // Draft, Confirmed, Done, Cancelled
            $table->date('receipt_date');
            $table->decimal('valuation_total', 15, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('po_id')->on('purchase_orders')->onDelete('cascade');
        });

        Schema::create('receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_id');
            $table->string('variant_sku');
            $table->decimal('ordered_qty', 10, 2);
            $table->decimal('received_qty', 10, 2);
            $table->decimal('accepted_qty', 10, 2);
            $table->decimal('rejected_qty', 10, 2)->default(0);
            $table->decimal('unit_purchase_price', 15, 2);
            $table->decimal('standard_cost', 15, 2);
            $table->decimal('valuation_amount', 15, 2);
            $table->timestamps();

            $table->foreign('receipt_id')->references('receipt_id')->on('receipts')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_lines');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
