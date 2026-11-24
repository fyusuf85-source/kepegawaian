<?php

namespace Tests\Feature;

use App\Models\ApprovalInstance;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $username): User { return User::where('username', $username)->firstOrFail(); }

    public function test_approval_flow_has_payroll_steps(): void
    {
        $flow = \App\Models\ApprovalFlow::where('code', 'payroll')->firstOrFail();
        $roles = $flow->steps->pluck('role.code')->all();
        $this->assertSame(['keuangan', 'pimpinan'], $roles);
    }

    public function test_hrd_submits_payroll_for_approval_and_flow_completes(): void
    {
        $period = PayrollPeriod::create([
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'dihitung',
        ]);

        $hrd = $this->user('hrd.demo');
        $this->actingAs($hrd)->post(route('payroll.submit', $period))->assertRedirect();

        $instance = ApprovalInstance::query()->where('subject_type', $period->getMorphClass())->where('subject_id', $period->id)->firstOrFail();
        $this->assertSame('menunggu', $instance->status);
        $this->assertSame('verifikasi_hrd', $period->fresh()->status);

        $this->actingAs($this->user('keuangan.demo'))->post(route('approvals.approve', $instance))->assertRedirect();
        $this->assertSame(2, $instance->fresh()->current_step);

        $this->actingAs($this->user('pimpinan.demo'))->post(route('approvals.approve', $instance))->assertRedirect();
        $this->assertSame('disetujui', $instance->fresh()->status);
        $this->assertSame('disetujui', $period->fresh()->status);
    }

    public function test_payroll_lock_blocked_without_approval(): void
    {
        $period = PayrollPeriod::create([
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'dihitung',
        ]);

        $response = $this->actingAs($this->user('keuangan.demo'))->post(route('payroll.lock', $period));
        $response->assertSessionHasErrors('period');
    }
}
