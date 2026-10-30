<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('contact_id')->unique(); // C-001, ..., V-01, ...
            $table->string('name');
            $table->string('contact_type'); // Customer, Vendor
            $table->string('customer_type')->nullable(); // Individu, Perusahaan
            $table->string('city');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('payment_terms');
            $table->decimal('opening_ar', 15, 2)->default(0);
            $table->decimal('opening_ap', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

