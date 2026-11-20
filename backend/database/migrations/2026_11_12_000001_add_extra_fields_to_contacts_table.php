<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('customer_type');
            }
            if (!Schema::hasColumn('contacts', 'phone')) {
                $table->string('phone')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('contacts', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('contacts', 'address')) {
                $table->text('address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('contacts', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('address');
            }
            if (!Schema::hasColumn('contacts', 'status')) {
                $table->string('status')->default('active')->after('tax_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'phone', 'email', 'address', 'tax_id', 'status']);
        });
    }
};
