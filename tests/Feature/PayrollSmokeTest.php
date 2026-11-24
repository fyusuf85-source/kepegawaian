<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function hrd(): User
    {
        return User::where('username', 'hrd.demo')->firstOrFail();
    }

    private function finance(): User
    {
        return User::where('username', 'keuangan.demo')->firstOrFail();
    }

    public function test_hrd_can_access_payroll_index(): void
    {
        $response = $this->actingAs($this->hrd())->get(route('payroll.index'));
        $response->assertStatus(200);
        $response->assertSee('Periode Payroll');
    }

    public function test_can_create_payroll_period(): void
    {
        $data = [
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'payment_date' => '2026-12-01',
            'notes' => 'Test period',
        ];

        $response = $this->actingAs($this->finance())->post(route('payroll.store'), $data);
        $response->assertRedirect();
        $this->assertDatabaseHas('payroll_periods', ['code' => '2026-11']);
    }

    public function test_can_generate_snapshot_and_calculate_skeleton(): void
    {
        $period = PayrollPeriod::create([
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->finance())->post(route('payroll.snapshot', $period));
        $response->assertSessionHas('success', 'Snapshot absensi dibuat.');
        $this->assertEquals('dihitung', $period->fresh()->status);

        $response = $this->actingAs($this->finance())->post(route('payroll.calculate', $period));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payroll_runs', ['payroll_period_id' => $period->id, 'version' => 1]);
    }

    public function test_can_export_payroll_report(): void
    {
        $period = PayrollPeriod::create([
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'draft',
        ]);

        $this->actingAs($this->finance())->post(route('payroll.snapshot', $period));
        $this->actingAs($this->finance())->post(route('payroll.calculate', $period));

        $response = $this->actingAs($this->finance())->get(route('payroll.export', $period));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_can_export_operational_payroll_reports(): void
    {
        $period = PayrollPeriod::create([
            'code' => '2026-12',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => 'draft',
        ]);
        $this->actingAs($this->finance())->post(route('payroll.snapshot', $period));
        $this->actingAs($this->finance())->post(route('payroll.calculate', $period));

        foreach (['transfer_gaji', 'transfer_snack', 'bpjs_tk', 'bpjs_kes', 'pajak'] as $report) {
            $this->actingAs($this->finance())->get(route('payroll.export', [$period, 'report' => $report]))
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
    }

    public function test_can_access_payroll_components(): void
    {
        $response = $this->actingAs($this->hrd())->get(route('payroll.components.index'));
        $response->assertStatus(200);
        $response->assertSee('Gaji Harian');
    }

    public function test_admin_cannot_access_payroll(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('payroll.index'));
        $response->assertStatus(403);
    }

    public function test_can_access_and_export_payroll_report(): void
    {
        $period = PayrollPeriod::create([
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'draft',
        ]);
        $this->actingAs($this->finance())->post(route('payroll.snapshot', $period));
        $this->actingAs($this->finance())->post(route('payroll.calculate', $period));

        $this->actingAs($this->hrd())->get(route('reports.payroll', ['period_code' => $period->code]))
            ->assertOk()
            ->assertSee('Rekap Penggajian');

        $this->actingAs($this->hrd())->get(route('reports.payroll.export', ['period_code' => $period->code]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
