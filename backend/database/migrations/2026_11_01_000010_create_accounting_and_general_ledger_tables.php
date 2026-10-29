<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code')->unique(); // 1010, 1100, etc.
            $table->string('account_name');
            $table->string('type'); // Aset, Liabilitas, Ekuitas, Pendapatan, Kontra Pendapatan, Beban
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_id')->unique(); // JE/2026/0008, JE/OPENING ...
            $table->date('entry_date');
            $table->string('reference_type'); // Opening, Delivery Order, Customer Invoice, Receipt, Vendor Bill, Payment, Return, Credit Note
            $table->string('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('Posted'); // Draft, Posted
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->boolean('is_posted')->default(true);
            $table->timestamps();

            // Ensure idempotency: one business reference can only produce one posted journal entry
            $table->unique(['reference_type', 'reference_id']);
        });

        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->string('entry_id');
            $table->string('account_code');
            $table->string('account_name')->nullable();
            $table->string('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('entry_id')->references('entry_id')->on('journal_entries')->onDelete('cascade');
            $table->foreign('account_code')->references('account_code')->on('chart_of_accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
