<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->unique(); // INV/2026/0008, BILL/2026/0008 ...
            $table->string('type'); // Customer Invoice, Vendor Bill
            $table->string('partner_id'); // Customer or Vendor ID
            $table->string('source_doc_type'); // Sales Order, Purchase Order, Delivery Order, Receipt, Table Order
            $table->string('source_doc_id');
            $table->string('linked_delivery_id')->nullable();
            $table->string('linked_receipt_id')->nullable();
            $table->date('invoice_date');
            $table->string('status')->default('Draft'); // Draft, Posted, Paid, Cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('pbjt_amount', 15, 2)->default(0);
            $table->decimal('price_variance_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id');
            $table->string('variant_sku');
            $table->decimal('invoiced_qty', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('pbjt_rate', 5, 2)->default(0);
            $table->decimal('pbjt_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();

            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->onDelete('cascade');
            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique(); // PAY/0008 ...
            $table->string('invoice_id');
            $table->string('payment_type'); // Customer Payment, Vendor Payment
            $table->string('payment_method')->default('Bank'); // Bank, Kas Restoran
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('remaining_due', 15, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('invoice_id')->on('invoices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};

