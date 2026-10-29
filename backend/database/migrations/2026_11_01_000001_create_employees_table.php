<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique(); // E-01, E-02, ..., E-09
            $table->string('name');
            $table->string('job_position');
            $table->string('department');
            $table->string('status')->default('Aktif'); // Aktif, Tidak Aktif
            $table->string('private_phone')->nullable();
            $table->string('private_email')->nullable();
            $table->text('private_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
