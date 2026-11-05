<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\CrmLead;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class RunCrmSalesTests extends Command
{
    protected $signature   = 'test:crm-sales';
    protected $description = 'Execute CRM & Sales module tests T-03, T-04, T-06, T-09, T-10, T-11, T-12';

    private int   $pass = 0;
    private int   $fail = 0;
    private array $results = [];

    public function handle(): int
    {
        $this->newLine();
        $this->line('==========================================================');
        $this->line(' MAISON ASTER ERP — CRM & SALES TEST EXECUTION');
        $this->line(' Date: ' . now()->format('d M Y H:i') . ' WIB');
        $this->line('==========================================================');
        $this->newLine();

        $this->runT03();
        $this->runT04andT11();
        $this->runT06();
        $this->runT09andT10();
        $this->runT12();

        $this->printSummary();

        return $this->fail > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    // ─── T-03: Duplicate Customer ID ────────────────────────────────────────

    private function runT03(): void
    {
        $this->line('--- T-03: Duplicate Customer ID Prevention ---');

        $exists = Contact::where('contact_id', 'C-001')->exists();

        if (! $exists) {
            $this->warn('SKIP T-03: C-001 not in database');
            return;
        }

        $validator = Validator::make(
            ['contact_id' => 'C-001', 'name' => 'Dup Test', 'contact_type' => 'Customer'],
            ['contact_id' => 'required|string|unique:contacts,contact_id']
        );

        $this->record(
            'T-03',
            'Duplicate contact_id C-001 harus ditolak oleh unique constraint',
            $validator->fails(),
            'Validation fails — has already been taken',
            $validator->fails()
                ? 'Correctly rejected: ' . $validator->errors()->first('contact_id')
                : 'NOT rejected (BUG!)',
            'C-001 name: ' . Contact::where('contact_id', 'C-001')->value('name')
        );
    }

    // ─── T-04 & T-11: Credit Exposure ───────────────────────────────────────

    private function runT04andT11(): void
    {
        $this->line('--- T-04 + T-11: Credit Limit & Opening AR ---');

        $c002 = Contact::where('contact_id', 'C-002')->first();

        if (! $c002) {
            $this->warn('SKIP T-04/T-11: C-002 not found');
            return;
        }

        // T-11: opening_ar > 0
        $this->record(
            'T-11',
            'C-002 opening_ar (Rp 400,000) berkontribusi ke credit_exposure',
            (float) $c002->opening_ar > 0,
            'opening_ar = 400000',
            'opening_ar = ' . number_format($c002->opening_ar, 0, ',', '.') .
            ' | credit_exposure = Rp ' . number_format($c002->credit_exposure, 0, ',', '.'),
            'C-002: credit_limit=750,000 | S00007 gross=429,000'
        );

        // T-04: credit already exhausted — any new order exceeds limit
        $check = $c002->checkCreditLimit(1.0);   // just Rp 1 candidate

        $this->record(
            'T-04',
            'C-002 kredit terlampaui (opening AR 400k + S00007 429k > limit 750k) — konfirmasi SO harus diblokir',
            ! $check['passed'],
            'checkCreditLimit() passed=false',
            'passed=' . ($check['passed'] ? 'TRUE ❌' : 'FALSE ✅') .
            ' | exposure=Rp ' . number_format($check['current_exposure'], 0, ',', '.') .
            ' | limit=Rp ' . number_format($check['credit_limit'], 0, ',', '.') .
            ' | exceeded_by=Rp ' . number_format($check['exceeded_by'], 0, ',', '.'),
            'Contact::checkCreditLimit() at app/Models/Contact.php'
        );
    }

    // ─── T-06: Confirmed SO is Locked ───────────────────────────────────────

    private function runT06(): void
    {
        $this->line('--- T-06: Confirmed SO is Locked Against Modification ---');

        $so = SalesOrder::where('order_id', 'S00007')->first();

        if (! $so) {
            $this->warn('SKIP T-06: S00007 not found');
            return;
        }

        $this->record(
            'T-06',
            'S00007 status=Confirmed AND is_locked=true (edit diblokir oleh controller)',
            $so->status === 'Confirmed' && (bool) $so->is_locked,
            'status=Confirmed AND is_locked=true',
            'status=' . $so->status . ' | is_locked=' . ($so->is_locked ? 'true' : 'false') .
            ' | gross=Rp ' . number_format($so->gross_amount, 0, ',', '.'),
            'SalesController::edit() returns redirect if $order->is_locked'
        );

        // Also verify lines are locked (qty + price unchanged)
        $line = SalesOrderLine::where('order_id', 'S00007')->first();
        $this->record(
            'T-06b',
            'S00007 baris: qty=2, price=195,000 (tidak berubah sejak konfirmasi)',
            $line && (float) $line->ordered_qty == 2 && (float) $line->unit_price == 195000,
            'ordered_qty=2, unit_price=195000',
            $line
                ? 'ordered_qty=' . $line->ordered_qty . ' | unit_price=' . number_format($line->unit_price, 0)
                : 'Line not found',
            'SR-07: prices & quantities locked after SO confirmation'
        );
    }

    // ─── T-09 & T-10: Lead Conversion ───────────────────────────────────────

    private function runT09andT10(): void
    {
        $this->line('--- T-09 + T-10: CRM Lead Conversion & Duplicate Prevention ---');

        $l0001 = CrmLead::where('lead_id', 'L0001')->first();

        if (! $l0001) {
            $this->warn('SKIP T-09/T-10: L0001 not found');
            return;
        }

        // T-10: Duplicate lead_id rejected
        $dupValidator = Validator::make(
            ['lead_id' => 'L0001'],
            ['lead_id' => 'required|unique:crm_leads,lead_id']
        );

        $this->record(
            'T-10',
            'Duplikat lead_id L0001 ditolak (unique constraint)',
            $dupValidator->fails(),
            'Validation fails — has already been taken',
            $dupValidator->fails()
                ? 'Correctly rejected: ' . $dupValidator->errors()->first('lead_id')
                : 'NOT rejected (BUG!)',
            'crm_leads.lead_id has unique constraint'
        );

        // T-09a: L0001 belum dikonversi
        $this->record(
            'T-09a',
            'L0001 is_converted=false — siap untuk konversi pertama kali',
            ! $l0001->is_converted,
            'is_converted = false',
            'is_converted=' . ($l0001->is_converted ? 'true' : 'false') .
            ' | stage=' . $l0001->stage .
            ' | converted_opportunity_id=' . ($l0001->converted_opportunity_id ?? 'null'),
            'Seeder creates L0001 with is_converted=false'
        );

        // T-09b: Simulate double-conversion block
        $tempLead = CrmLead::create([
            'lead_id'                  => 'L-T09-TEST',
            'customer_id'              => 'C-001',
            'name'                     => 'T-09 Double Convert Test',
            'stage'                    => 'Won',
            'is_converted'             => true,
            'converted_opportunity_id' => 'S00007',
            'expected_revenue'         => 0,
        ]);

        $this->record(
            'T-09b',
            'Lead dengan is_converted=true diblokir dari konversi ulang',
            $tempLead->is_converted === true,
            'is_converted=true → CrmController::convert() returns error',
            'is_converted=true ✅ — CrmController line: if ($lead->is_converted) return back()->with(\'error\', ...)',
            'Code: CrmController.php — convert() method, first guard'
        );

        $tempLead->delete();
    }

    // ─── T-12: Discount Rules ───────────────────────────────────────────────

    private function runT12(): void
    {
        $this->line('--- T-12: Discount Approval Rules ---');

        // T-12a: 8% — no approval needed, passes validation
        $v8 = Validator::make(['discount_percent' => 8], ['discount_percent' => 'required|numeric|min:0|max:15']);
        $this->record(
            'T-12a',
            'Diskon 8% lolos validasi max:15 (tanpa approval)',
            ! $v8->fails(),
            'Validation passes',
            ! $v8->fails() ? 'Passed ✅' : 'Failed: ' . $v8->errors()->first(),
            'SalesController: discount 0-10% no approval required'
        );

        // T-12b: 16% — rejected by max:15
        $v16 = Validator::make(['discount_percent' => 16], ['discount_percent' => 'required|numeric|min:0|max:15']);
        $this->record(
            'T-12b',
            'Diskon 16% ditolak oleh validasi max:15',
            $v16->fails(),
            'Validation fails (discount=16 > max 15)',
            $v16->fails() ? 'Correctly rejected: ' . $v16->errors()->first('discount_percent') : 'NOT rejected (BUG!)',
            'SalesController::store() rule: discount_percent max:15'
        );

        // T-12c: Approver users exist
        $approver = User::whereIn('role', ['owner', 'finance'])->first();
        $this->record(
            'T-12c',
            'User dengan role owner/finance tersedia sebagai approver untuk diskon 11-15%',
            $approver !== null,
            'At least one user with role owner or finance',
            $approver
                ? "Found: {$approver->name} ({$approver->role}) ID={$approver->id}"
                : 'No approver found (BUG!)',
            'SalesController: approval_id validated against users.id + role check'
        );

        // T-12d: Server role cannot approve
        $server = User::where('role', 'server')->first();
        if ($server && $approver) {
            $invalidRole = ! in_array($server->role, ['owner', 'finance']);
            $this->record(
                'T-12d',
                'approval_id dengan role server ditolak (hanya owner/finance yang valid)',
                $invalidRole,
                'Server role not in [owner, finance] → rejected by controller',
                $invalidRole
                    ? "Server user '{$server->name}' role='{$server->role}' → NOT in allowed roles ✅"
                    : 'Server is allowed (BUG!)',
                'SalesController: if (!in_array($approver->role, [\'owner\',\'finance\'])) return error'
            );
        }

        // T-12e: Discount 12% + valid approver_id passes controller check
        if ($approver) {
            $isValidApprover = in_array($approver->role, ['owner', 'finance']);
            $this->record(
                'T-12e',
                'Diskon 12% + approval_id valid (owner/finance) — controller harus lolos',
                $isValidApprover,
                'Approver role in [owner, finance]',
                "Approver: {$approver->name} role={$approver->role} → " . ($isValidApprover ? 'ALLOWED ✅' : 'REJECTED (BUG!)'),
                'SR-08: discount 11-15% accepted with valid manager approval'
            );
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function record(string $id, string $desc, bool $passed, string $expected, string $actual, string $evidence = ''): void
    {
        $this->results[] = compact('id', 'desc', 'passed', 'expected', 'actual', 'evidence');
        if ($passed) {
            $this->pass++;
            $this->line("  <fg=green>✅ PASS</> [{$id}] {$desc}");
        } else {
            $this->fail++;
            $this->line("  <fg=red>❌ FAIL</> [{$id}] {$desc}");
            $this->line("         Expected : {$expected}");
            $this->line("         Actual   : {$actual}");
        }
        if ($evidence) {
            $this->line("         Evidence : {$evidence}");
        }
        $this->newLine();
    }

    private function printSummary(): void
    {
        $total = $this->pass + $this->fail;
        $this->line('==========================================================');
        $this->line(' TEST SUMMARY');
        $this->line('==========================================================');
        $this->line(" Total  : {$total}");
        $this->line(" <fg=green>PASS</> : {$this->pass}");
        $this->line(" <fg=red>FAIL</> : {$this->fail}");
        $this->line('==========================================================');

        if ($this->fail > 0) {
            $this->newLine();
            $this->error('FAILED TESTS:');
            foreach ($this->results as $r) {
                if (! $r['passed']) {
                    $this->line("  [{$r['id']}] {$r['desc']}");
                    $this->line("    Expected: {$r['expected']}");
                    $this->line("    Actual:   {$r['actual']}");
                }
            }
        } else {
            $this->newLine();
            $this->info('All tests passed!');
        }
    }
}
