<?php

namespace Tests\Feature;

use App\Imports\EmployeesImport;
use App\Models\Education;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\NumberSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Education::create([
            'code' => 'S1',
            'name' => 'Sarjana',
            'level_order' => 5,
            'is_active' => true,
        ]);

        NumberSequence::create([
            'code' => 'nip',
            'prefix' => 'PIS',
            'pattern' => '{prefix}-{year}-{seq:4}',
            'next_number' => 1,
            'padding' => 4,
            'reset_period' => 'yearly',
        ]);
    }

    public function test_baris_valid_diimpor_dan_batch_tercatat(): void
    {
        $actor = $this->userWithPermissions(['employee.import']);
        $batch = ImportBatch::create([
            'module' => 'employees',
            'file_name' => 'uji.xlsx',
            'status' => 'processing',
            'created_by' => $actor->id,
        ]);

        $import = new EmployeesImport($actor, $batch);
        $import->collection(collect([
            new Collection([
                'nama_lengkap' => 'Pegawai Impor Uji',
                'nik' => '3200000000000011',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Sidoarjo',
                'tanggal_lahir' => '12-03-1992',
                'telepon' => '081211111111',
                'email' => 'impor@example.test',
                'kategori' => 'tetap',
                'status_kepegawaian' => 'aktif',
                'tanggal_masuk' => '01-07-2026',
                'kode_pendidikan' => 'S1',
                'status_pajak' => 'TK/0',
                'jumlah_tanggungan' => '0',
            ]),
        ]));

        $batch->refresh();

        $this->assertSame(1, $batch->success_rows);
        $this->assertSame(0, $batch->failed_rows);
        $this->assertSame('completed', $batch->status);
        $this->assertSame('Pegawai Impor Uji', Employee::firstOrFail()->full_name);
        $this->assertSame('S1', Employee::firstOrFail()->payrollProfile->education->code);
    }

    public function test_baris_tidak_valid_dicatat_sebagai_gagal(): void
    {
        $actor = $this->userWithPermissions(['employee.import']);
        $batch = ImportBatch::create([
            'module' => 'employees',
            'file_name' => 'uji-gagal.xlsx',
            'status' => 'processing',
            'created_by' => $actor->id,
        ]);

        $import = new EmployeesImport($actor, $batch);
        $import->collection(collect([
            new Collection([
                'nama_lengkap' => 'Pegawai Gagal Uji',
                'jenis_kelamin' => 'X',
                'tanggal_masuk' => '01-07-2026',
            ]),
        ]));

        $batch->refresh();

        $this->assertSame(0, $batch->success_rows);
        $this->assertSame(1, $batch->failed_rows);
        $this->assertSame('failed', $batch->status);
        $this->assertNotEmpty($batch->errors);
        $this->assertSame(0, Employee::count());
    }
}
