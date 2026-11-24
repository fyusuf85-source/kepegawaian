<?php

namespace App\Imports;

use App\Models\Education;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\EmployeeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class EmployeesImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas, WithHeadingRow
{
    public function __construct(
        private readonly User $actor,
        private readonly ImportBatch $batch
    ) {
    }

    public function collection(Collection $rows): void
    {
        $total = $rows->count();
        $success = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = (int) $index + 2;
            $name = trim((string) ($row['nama_lengkap'] ?? ''));

            if ($name === '' || str_starts_with($name, 'CONTOH')) {
                $total--;
                continue;
            }

            try {
                $this->importRow($row);
                $success++;
            } catch (ValidationException $exception) {
                $errors[] = [
                    'row' => $line,
                    'messages' => collect($exception->errors())->flatten()->all(),
                ];
            } catch (Throwable $exception) {
                $errors[] = [
                    'row' => $line,
                    'messages' => [$exception->getMessage()],
                ];
            }
        }

        $failed = count($errors);
        $status = match (true) {
            $failed === 0 => 'completed',
            $success === 0 && $total > 0 => 'failed',
            default => 'completed_with_errors',
        };

        $this->batch->update([
            'total_rows' => max($total, 0),
            'success_rows' => $success,
            'failed_rows' => $failed,
            'status' => $status,
            'errors' => $errors === [] ? null : $errors,
        ]);
    }

    /**
     * @param  Collection<string, mixed>  $row
     */
    protected function importRow(Collection $row): void
    {
        $gender = match (strtolower(trim((string) ($row['jenis_kelamin'] ?? '')))) {
            'l', 'lk', 'laki-laki', 'laki laki', 'pria' => 'laki-laki',
            'p', 'pr', 'perempuan', 'wanita' => 'perempuan',
            default => null,
        };

        $nik = trim((string) ($row['nik'] ?? ''));

        $data = [
            'nip' => trim((string) ($row['nip'] ?? '')) ?: null,
            'full_name' => trim((string) $row['nama_lengkap']),
            'nik' => $nik ?: null,
            'gender' => $gender,
            'birth_place' => trim((string) ($row['tempat_lahir'] ?? '')) ?: null,
            'birth_date' => $this->parseDate($row['tanggal_lahir'] ?? null),
            'phone' => trim((string) ($row['telepon'] ?? '')) ?: null,
            'email' => trim((string) ($row['email'] ?? '')) ?: null,
            'employee_category' => strtolower(trim((string) ($row['kategori'] ?? 'tetap'))),
            'khidmah_type' => trim((string) ($row['jenis_khidmah'] ?? '')) ?: null,
            'employment_status' => strtolower(trim((string) ($row['status_kepegawaian'] ?? 'aktif'))),
            'hire_date' => $this->parseDate($row['tanggal_masuk'] ?? null),
            'marital_status' => 'lajang',
            'is_active' => true,
        ];

        $validator = Validator::make($data, [
            'nip' => ['nullable', 'string', 'max:30', 'unique:employees,nip'],
            'full_name' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'digits:16'],
            'gender' => ['required', 'in:laki-laki,perempuan'],
            'email' => ['nullable', 'email', 'max:150'],
            'employee_category' => ['required', 'in:tetap,harian,khidmah'],
            'employment_status' => ['required', 'in:aktif,probation,cuti_panjang,nonaktif,berhenti'],
            'hire_date' => ['required', 'date'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ], [
            'nip.unique' => 'NIP sudah digunakan.',
            'gender.required' => 'Jenis kelamin harus L atau P.',
            'hire_date.required' => 'Tanggal masuk wajib diisi (format DD-MM-YYYY).',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($nik) {
            $hash = hash('sha256', $nik);
            if (Employee::query()->where('nik_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['nik' => 'NIK sudah terdaftar.']);
            }
        }

        $unitCode = trim((string) ($row['kode_unit'] ?? ''));
        if ($unitCode !== '') {
            $unit = OrgUnit::query()->whereRaw('LOWER(code) = ?', [strtolower($unitCode)])->first();
            if (! $unit) {
                throw ValidationException::withMessages(['kode_unit' => "Kode unit '{$unitCode}' tidak ditemukan."]);
            }
            $data['org_unit_id'] = $unit->id;
        }

        $positionCode = trim((string) ($row['kode_jabatan'] ?? ''));
        if ($positionCode !== '') {
            $position = Position::query()->whereRaw('LOWER(code) = ?', [strtolower($positionCode)])->first();
            if (! $position) {
                throw ValidationException::withMessages(['kode_jabatan' => "Kode jabatan '{$positionCode}' tidak ditemukan."]);
            }
            $data['position_id'] = $position->id;
        }

        $locationCode = trim((string) ($row['kode_lokasi'] ?? ''));
        if ($locationCode !== '') {
            $location = WorkLocation::query()->whereRaw('LOWER(code) = ?', [strtolower($locationCode)])->first();
            if (! $location) {
                throw ValidationException::withMessages(['kode_lokasi' => "Kode lokasi '{$locationCode}' tidak ditemukan."]);
            }
            $data['work_location_id'] = $location->id;
        }

        $educationCode = trim((string) ($row['kode_pendidikan'] ?? ''));
        if ($educationCode !== '') {
            $education = Education::query()->whereRaw('LOWER(code) = ?', [strtolower($educationCode)])->first();
            if (! $education) {
                throw ValidationException::withMessages(['kode_pendidikan' => "Kode pendidikan '{$educationCode}' tidak ditemukan."]);
            }
            $data['education_id'] = $education->id;
        }

        $dependents = trim((string) ($row['jumlah_tanggungan'] ?? ''));
        if ($dependents !== '') {
            if (! is_numeric($dependents) || (int) $dependents < 0 || (int) $dependents > 3) {
                throw ValidationException::withMessages(['jumlah_tanggungan' => 'Jumlah tanggungan harus 0–3.']);
            }
            $data['dependents_count'] = (int) $dependents;
        }

        $taxStatus = trim((string) ($row['status_pajak'] ?? ''));
        if ($taxStatus !== '' && ! in_array($taxStatus, ['TK/0', 'TK/1', 'TK/2', 'TK/3', 'K/0', 'K/1', 'K/2', 'K/3'], true)) {
            throw ValidationException::withMessages(['status_pajak' => 'Status pajak tidak valid.']);
        }

        $employee = app(EmployeeService::class)->create($data, $this->actor);

        if ($taxStatus !== '') {
            $employee->payrollProfile()->update(['tax_status' => $taxStatus]);
        }
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim((string) $value));
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (Throwable) {
                // coba format berikutnya
            }
        }

        throw ValidationException::withMessages([
            'tanggal' => "Tanggal '{$value}' tidak dikenali. Gunakan format DD-MM-YYYY.",
        ]);
    }
}
