<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_id')->unique(); // L0001 ...
            $table->string('customer_id');
            $table->string('name');
            $table->string('stage')->default('Lead'); // Lead, Qualified, Proposition, Won, Lost
            $table->decimal('expected_revenue', 15, 2)->default(0);
            $table->string('converted_opportunity_id')->nullable()->unique();
            $table->boolean('is_converted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
