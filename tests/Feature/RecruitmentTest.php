<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Employee;
use App\Models\JobVacancy;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pelamar_diproses_hingga_menjadi_pegawai_tanpa_input_ulang_identitas(): void
    {
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $unit = OrgUnit::where('code', 'PIS-SMP')->firstOrFail();
        $position = Position::where('code', 'GURU')->firstOrFail();

        $this->actingAs($hrd)->post(route('vacancies.store'), [
            'title' => 'Guru Bahasa Arab (DEMO)',
            'org_unit_id' => $unit->id,
            'position_id' => $position->id,
            'employee_category' => 'tetap',
            'quota' => 1,
            'open_date' => now('Asia/Jakarta')->toDateString(),
            'close_date' => now('Asia/Jakarta')->addMonth()->toDateString(),
            'status' => 'dibuka',
        ])->assertRedirect();

        $vacancy = JobVacancy::where('title', 'Guru Bahasa Arab (DEMO)')->firstOrFail();
        $this->assertMatchesRegularExpression('/^LOW\/\d{4}\/\d{4}$/', $vacancy->code);

        $this->actingAs($hrd)->post(route('applicants.store'), [
            'vacancy_id' => $vacancy->id,
            'full_name' => 'DEMO — Pelamar Uji',
            'nik' => '3512345678901234',
            'birth_place' => 'Sidoarjo',
            'birth_date' => '1998-02-03',
            'gender' => 'laki-laki',
            'phone' => '081299998888',
            'email' => 'pelamar.uji@example.test',
            'address' => 'Jl. Uji Nomor 1',
            'education_id' => \App\Models\Education::where('code', 'S1')->value('id'),
            'experience_years' => 2,
            'source' => 'DEMO',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $applicant = Applicant::where('full_name', 'DEMO — Pelamar Uji')->firstOrFail();
        $this->assertSame('baru', $applicant->status);
        $this->assertSame('3512345678901234', $applicant->fresh()->nik);

        $this->actingAs($hrd)->post(route('applicants.status', $applicant), [
            'status' => 'seleksi_administrasi',
            'note' => 'Berkas lengkap',
        ])->assertRedirect();

        $this->actingAs($hrd)->post(route('applicants.status', $applicant), [
            'status' => 'wawancara',
            'note' => 'Jadwal wawancara dibuat',
        ])->assertRedirect();

        $this->actingAs($hrd)->post(route('applicants.status', $applicant), [
            'status' => 'dipertimbangkan',
            'note' => 'Lanjut keputusan',
        ])->assertRedirect();

        $this->actingAs($hrd)->post(route('applicants.scores.store', $applicant), [
            'scores' => [
                ['criterion' => 'Kompetensi', 'weight' => 60, 'score' => 80],
                ['criterion' => 'Wawancara', 'weight' => 40, 'score' => 90],
            ],
        ])->assertRedirect();

        $applicant->refresh();
        $this->assertSame('84.00', $applicant->final_score);

        $this->actingAs($hrd)->post(route('applicants.decide', $applicant), [
            'decision' => 'diterima',
            'reason' => 'Lolos seluruh tahapan',
        ])->assertRedirect();

        $this->assertSame('diterima', $applicant->fresh()->status);

        $this->actingAs($hrd)->post(route('applicants.convert', $applicant), [
            'marital_status' => 'lajang',
            'org_unit_id' => $unit->id,
            'position_id' => $position->id,
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => now('Asia/Jakarta')->toDateString(),
        ])->assertRedirect();

        $applicant->refresh();
        $this->assertNotNull($applicant->converted_employee_id);

        $employee = Employee::findOrFail($applicant->converted_employee_id);
        $this->assertSame('DEMO — Pelamar Uji', $employee->full_name);
        $this->assertSame('3512345678901234', $employee->nik);
        $this->assertSame('081299998888', $employee->phone);
        $this->assertSame($unit->id, $employee->org_unit_id);

        // Konversi kedua harus ditolak dan tidak membuat pegawai baru.
        $count = Employee::count();
        $this->actingAs($hrd)->post(route('applicants.convert', $applicant), [
            'marital_status' => 'lajang',
            'org_unit_id' => $unit->id,
            'position_id' => $position->id,
            'employee_category' => 'tetap',
            'employment_status' => 'aktif',
            'hire_date' => now('Asia/Jakarta')->toDateString(),
        ])->assertSessionHasErrors('applicant');

        $this->assertSame($count, Employee::count());
    }

    public function test_total_bobot_penilaian_harus_100(): void
    {
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $applicant = $this->applicantFor($hrd);

        $this->actingAs($hrd)->post(route('applicants.scores.store', $applicant), [
            'scores' => [
                ['criterion' => 'Kompetensi', 'weight' => 60, 'score' => 80],
                ['criterion' => 'Wawancara', 'weight' => 30, 'score' => 90],
            ],
        ])->assertSessionHasErrors('scores');

        $this->assertSame(0, $applicant->scores()->count());
    }

    public function test_penerimaan_hanya_setelah_tahap_dipertimbangkan(): void
    {
        $hrd = User::where('username', 'hrd.demo')->firstOrFail();
        $applicant = $this->applicantFor($hrd);

        $this->actingAs($hrd)->post(route('applicants.decide', $applicant), [
            'decision' => 'diterima',
            'reason' => 'Terlalu cepat',
        ])->assertSessionHasErrors('decision');

        $this->assertSame('baru', $applicant->fresh()->status);
    }

    protected function applicantFor(User $hrd): Applicant
    {
        $unit = OrgUnit::where('code', 'PIS-SMP')->firstOrFail();

        $vacancy = JobVacancy::create([
            'code' => 'LOW/UJI/0001',
            'title' => 'Lowongan Uji',
            'org_unit_id' => $unit->id,
            'employee_category' => 'tetap',
            'quota' => 1,
            'open_date' => now('Asia/Jakarta')->subDay()->toDateString(),
            'close_date' => now('Asia/Jakarta')->addMonth()->toDateString(),
            'status' => 'dibuka',
            'created_by' => $hrd->id,
        ]);

        return Applicant::create([
            'vacancy_id' => $vacancy->id,
            'code' => 'PLM/UJI/0001',
            'full_name' => 'DEMO — Pelamar Skor',
            'phone' => '081200001111',
            'status' => 'baru',
        ]);
    }
}
