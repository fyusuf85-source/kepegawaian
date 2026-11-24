<?php

namespace Tests\Feature;

use App\Models\CoachingRecord;
use App\Models\Employee;
use App\Models\KpiAssessment;
use App\Models\KpiPeriod;
use App\Models\KpiTemplate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function test_full_kpi_workflow_from_assessment_to_approval(): void
    {
        $hrd = $this->user('hrd.demo');
        $kepala = $this->user('kepala.demo');
        $pimpinan = $this->user('pimpinan.demo');
        $pegawai = $this->user('pegawai.demo')->employee;
        $template = KpiTemplate::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($hrd)->post(route('kpi.periods.store'), [
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'notes' => 'Periode uji',
        ])->assertSessionHas('success');

        $period = KpiPeriod::query()->where('code', '2026-11')->firstOrFail();

        $this->actingAs($kepala)->post(route('kpi.assessments.store'), [
            'employee_id' => $pegawai->id,
            'kpi_period_id' => $period->id,
            'kpi_template_id' => $template->id,
        ])->assertRedirect();

        $assessment = KpiAssessment::query()->firstOrFail();
        $this->assertEquals('draft', $assessment->status);

        $items = $assessment->items()->get()->values()->map(fn ($item, $i) => [
            'id' => $item->id,
            'target' => 100,
            'actual' => 100,
            'notes' => 'Uji '.$i,
        ])->all();

        $this->actingAs($kepala)->put(route('kpi.assessments.update', $assessment), [
            'items' => $items,
            'notes' => 'Catatan penilai',
        ])->assertSessionHas('success');

        $this->assertEquals(100.0, (float) $assessment->fresh()->final_score);

        $this->actingAs($kepala)->post(route('kpi.assessments.submit', $assessment))->assertSessionHas('success');
        $this->assertEquals('submitted', $assessment->fresh()->status);

        $this->actingAs($hrd)->post(route('kpi.assessments.verify', $assessment))->assertSessionHas('success');
        $this->assertEquals('verified', $assessment->fresh()->status);

        $this->actingAs($pimpinan)->post(route('kpi.assessments.approve', $assessment))->assertSessionHas('success');
        $this->assertEquals('approved', $assessment->fresh()->status);

        $employeeUser = User::query()->where('employee_id', $pegawai->id)->firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $employeeUser->id,
            'type' => 'kpi',
            'title' => 'Penilaian KPI disetujui',
        ]);
        $this->assertTrue(Notification::query()->where('type', 'kpi')->whereNotNull('read_at')->doesntExist());
    }

    public function test_assessor_cannot_assess_self(): void
    {
        $hrd = $this->user('hrd.demo');
        $kepala = $this->user('kepala.demo');
        $template = KpiTemplate::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($hrd)->post(route('kpi.periods.store'), [
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);

        $period = KpiPeriod::query()->where('code', '2026-11')->firstOrFail();

        $response = $this->actingAs($kepala)->post(route('kpi.assessments.store'), [
            'employee_id' => $kepala->employee_id,
            'kpi_period_id' => $period->id,
            'kpi_template_id' => $template->id,
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('kpi_assessments', 0);
    }

    public function test_coaching_record_can_be_created_and_approved(): void
    {
        $kepala = $this->user('kepala.demo');
        $pimpinan = $this->user('pimpinan.demo');
        $pegawai = Employee::query()->whereKey($this->user('pegawai.demo')->employee_id)->firstOrFail();

        $this->actingAs($kepala)->post(route('coaching.store'), [
            'employee_id' => $pegawai->id,
            'date' => '2026-11-20',
            'type' => 'konseling',
            'reason' => 'Koordinasi rutin',
            'result' => 'Disepakati perbaikan jadwal',
        ])->assertSessionHas('success');

        $record = CoachingRecord::query()->firstOrFail();
        $this->assertEquals('draft', $record->status);

        $this->actingAs($pimpinan)->post(route('coaching.approve', $record))->assertSessionHas('success');
        $this->assertEquals('disetujui', $record->fresh()->status);

        $this->assertDatabaseHas('notifications', [
            'type' => 'coaching',
            'title' => 'Catatan pembinaan disetujui',
        ]);
    }

    public function test_hrd_can_access_executive_and_attendance_reports(): void
    {
        $hrd = $this->user('hrd.demo');

        $this->actingAs($hrd)->get(route('reports.executive'))->assertOk()->assertSee('Laporan Eksekutif');
        $this->actingAs($hrd)->get(route('reports.attendance'))->assertOk()->assertSee('Rekapitulasi Absensi');
        $this->actingAs($hrd)->get(route('reports.attendance.export'))->assertOk();
    }

    public function test_employee_cannot_access_other_employee_assessment(): void
    {
        $hrd = $this->user('hrd.demo');
        $kepala = $this->user('kepala.demo');
        $pegawai = $this->user('pegawai.demo');
        $template = KpiTemplate::query()->where('is_active', true)->firstOrFail();

        $period = KpiPeriod::query()->create([
            'code' => '2026-11',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'open',
        ]);

        $assessment = KpiAssessment::query()->create([
            'kpi_period_id' => $period->id,
            'kpi_template_id' => $template->id,
            'employee_id' => $kepala->employee_id,
            'assessor_id' => $hrd->employee_id,
            'status' => 'draft',
            'final_score' => 0,
        ]);

        $this->actingAs($pegawai)->get(route('kpi.assessments.show', $assessment))->assertStatus(403);
    }
}
