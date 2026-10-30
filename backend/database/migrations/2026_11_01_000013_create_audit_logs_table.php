<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_name')->default('System');
            $table->string('actor_role')->default('System');
            $table->string('action'); // CONFIRM, POST, ATTEMPT_DUPLICATE, ATTEMPT_PRICE_EDIT, etc.
            $table->string('document_type'); // Sales Order, Invoice, Table Order, Employee, etc.
            $table->string('document_id');
            $table->string('reference_code')->nullable();
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

