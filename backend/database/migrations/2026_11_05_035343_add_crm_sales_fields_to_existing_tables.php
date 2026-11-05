<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. contacts: add phone, email, address ────────────────────────
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('city');
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('tax_id')->nullable()->after('address');
            $table->string('status')->default('active')->after('tax_id'); // active, inactive
        });

        // ── 2. crm_leads: add missing CRM fields ─────────────────────────
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->string('contact_person')->nullable()->after('company_name');
            $table->string('email')->nullable()->after('contact_person');
            $table->string('phone')->nullable()->after('email');
            $table->string('lead_source')->nullable()->after('phone');
            // Source options: Website, Referral, Walk-in, Event, Social Media, Cold Call, Other
            $table->decimal('probability', 5, 2)->default(0)->after('expected_revenue');
            $table->date('closing_date')->nullable()->after('probability');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('closing_date');
            // assigned_to references users.id — soft FK, no constraint to allow flexibility
            $table->string('lead_status')->default('New')->after('assigned_to');
            // Status: New, Contacted, Qualified, Unqualified
            // Note: 'stage' field still used for pipeline stage (Lead/Qualified/Proposition/Won/Lost)
            $table->date('conversion_date')->nullable()->after('is_converted');
        });

        // ── 3. sales_orders: add crm_lead_id back-reference + quotation ref ──
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('crm_lead_id')->nullable()->after('customer_id');
            $table->string('quotation_id')->nullable()->after('crm_lead_id');
            $table->string('salesperson_id')->nullable()->after('quotation_id');
            // salesperson_id references users.id (string for flexibility)
            $table->date('confirmation_date')->nullable()->after('order_date');
            $table->string('terms_conditions')->nullable()->after('notes');
        });

        // ── 4. New: quotations table (separate from sales_orders) ─────────
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_id')->unique(); // Q/2026/0001
            $table->string('customer_id');            // FK → contacts.contact_id
            $table->string('crm_lead_id')->nullable(); // FK → crm_leads.lead_id
            $table->date('quotation_date');
            $table->date('expiration_date')->nullable();
            $table->string('salesperson_id')->nullable(); // FK → users.id
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->string('approval_id')->nullable();    // FK → users.id (for >10% discount)
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('pbjt_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->string('status')->default('Draft');
            // Draft, Sent, Accepted, Rejected, Expired, Converted
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('contact_id')->on('contacts');
        });

        // ── 5. New: quotation_lines table ────────────────────────────────
        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_id');           // FK → quotations.quotation_id
            $table->string('variant_sku');             // FK → products.variant_sku
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('pbjt_rate', 5, 4)->default(0.10);
            $table->decimal('pbjt_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();

            $table->foreign('quotation_id')->references('quotation_id')->on('quotations')->onDelete('cascade');
        });

        // ── 6. New: discount_approvals table ─────────────────────────────
        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approval_code')->unique(); // DA/2026/0001
            $table->string('document_type');           // Quotation, SalesOrder
            $table->string('document_id');             // quotation_id or order_id
            $table->string('customer_id');
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('document_amount', 15, 2)->default(0);
            $table->string('requested_by');            // user name
            $table->string('requested_by_role');
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            $table->string('approved_by')->nullable();    // user name
            $table->string('approved_by_role')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // ── 7. New: credit_validations table ─────────────────────────────
        Schema::create('credit_validations', function (Blueprint $table) {
            $table->id();
            $table->string('validation_code')->unique(); // CV/2026/0001
            $table->string('document_type');            // Quotation, SalesOrder
            $table->string('document_id');
            $table->string('customer_id');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('opening_ar', 15, 2)->default(0);
            $table->decimal('unpaid_invoices', 15, 2)->default(0);
            $table->decimal('confirmed_so_amount', 15, 2)->default(0);
            $table->decimal('current_exposure', 15, 2)->default(0);
            $table->decimal('candidate_amount', 15, 2)->default(0);
            $table->decimal('projected_exposure', 15, 2)->default(0);
            $table->boolean('passed')->default(false);
            $table->decimal('exceeded_by', 15, 2)->default(0);
            $table->string('validated_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_validations');
        Schema::dropIfExists('discount_approvals');
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['crm_lead_id', 'quotation_id', 'salesperson_id', 'confirmation_date', 'terms_conditions']);
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'contact_person', 'email', 'phone', 'lead_source', 'probability', 'closing_date', 'assigned_to', 'lead_status', 'conversion_date']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'address', 'tax_id', 'status']);
        });
    }
};
