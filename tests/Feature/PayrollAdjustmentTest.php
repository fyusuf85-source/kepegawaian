<?php

namespace Tests\Feature;

use App\Models\AttendancePeriodSummary;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_approved_adjustment_becomes_auditable_payroll_detail_in_new_run(): void
    {
        $finance = User::where('username', 'keuangan.demo')->firstOrFail();
        $employee = Employee::query()->where('is_active', true)->firstOrFail();
        $period = PayrollPeriod::create(['code' => '2026-11', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'status' => 'draft']);
        AttendancePeriodSummary::create(['payroll_period_id' => $period->id, 'employee_id' => $employee->id, 'work_days' => 0, 'present_days' => 0, 'late_days' => 0, 'total_late_minutes' => 0, 'early_leave_days' => 0, 'total_early_minutes' => 0, 'absent_days' => 0, 'paid_leave_days' => 0, 'unpaid_leave_days' => 0, 'holiday_days' => 0, 'special_days' => 0, 'generated_at' => now()]);
        $component = PayrollComponent::where('code', 'tunjangan_jabatan')->firstOrFail();
        $adjustment = PayrollAdjustment::create(['payroll_period_id' => $period->id, 'employee_id' => $employee->id, 'component_id' => $component->id, 'type' => 'penghasilan', 'amount' => 125000, 'reason' => 'Penyesuaian jabatan disetujui', 'status' => 'disetujui', 'requested_by' => $finance->id, 'approved_by' => $finance->id]);

        $this->actingAs($finance)->post(route('payroll.calculate', $period))->assertRedirect();

        $this->assertDatabaseHas('payroll_details', ['component_id' => $component->id, 'source_type' => 'manual_adjustment', 'source_id' => $adjustment->id, 'amount' => 125000]);
        $runEmployee = \App\Models\PayrollRunEmployee::query()->where('employee_id', $employee->id)->latest('id')->firstOrFail();
        $this->assertEquals(125000, (float) $runEmployee->details()->where('source_type', 'manual_adjustment')->sum('amount'));
        $this->assertDatabaseHas('payroll_adjustments', ['id' => $adjustment->id, 'applied_run_id' => $runEmployee->payroll_run_id]);
    }
}
