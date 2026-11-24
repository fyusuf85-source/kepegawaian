<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayslipGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayslipPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_generates_private_individual_payslip_pdf_for_locked_period(): void
    {
        $period = PayrollPeriod::create(['code' => '2026-11', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'status' => 'dikunci']);
        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'version' => 1, 'status' => 'final']);
        $employee = Employee::query()->whereNotNull('nip')->firstOrFail();
        PayrollRunEmployee::create(['payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'gross' => 0, 'total_deduction' => 0, 'net' => 0, 'status' => 'dihitung']);

        $count = app(PayslipGenerationService::class)->generate($period);

        $this->assertSame(1, $count);
        $slip = Payslip::query()->firstOrFail();
        Storage::disk('local')->assertExists($slip->file_path);
    }

    public function test_employee_cannot_download_another_employee_payslip(): void
    {
        $employee = Employee::query()->whereNotNull('nip')->firstOrFail();
        $period = PayrollPeriod::create(['code' => '2026-11', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'status' => 'dikunci']);
        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'version' => 1, 'status' => 'final']);
        $slip = Payslip::create(['payroll_period_id' => $period->id, 'payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'gross' => 0, 'total_deduction' => 0, 'net' => 0, 'file_path' => 'payslips/test.pdf']);
        Storage::disk('local')->put('payslips/test.pdf', 'pdf');

        $other = User::where('username', 'pegawai.demo')->firstOrFail();
        if ($other->employee_id === $employee->id) $other = User::where('username', 'hrd.demo')->firstOrFail();
        $this->actingAs($other)->get(route('payslips.download', $slip))->assertForbidden();
    }
}
