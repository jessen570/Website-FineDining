<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_id')->unique(); // RET/0008 ...
            $table->string('original_invoice_id');
            $table->string('customer_id');
            $table->string('status')->default('Draft'); // Draft, Validated, Posted
            $table->string('variant_sku');
            $table->decimal('quantity', 10, 2);
            $table->decimal('credit_subtotal', 15, 2);
            $table->decimal('pbjt_reversal', 15, 2);
            $table->decimal('total_credit_note', 15, 2);
            $table->decimal('cogs_reversal', 15, 2)->default(0);
            $table->string('credit_note_id')->nullable()->unique();
            $table->boolean('is_posted')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('variant_sku')->references('variant_sku')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
