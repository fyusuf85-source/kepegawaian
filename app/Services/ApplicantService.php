<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicantService
{
    public function __construct(private readonly EmployeeService $employees)
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $scores
     */
    public function saveScores(Applicant $applicant, array $scores, User $actor): void
    {
        $total = collect($scores)->sum(fn (array $row) => (float) ($row['weight'] ?? 0));

        if ($scores !== [] && abs($total - 100) > 0.01) {
            throw ValidationException::withMessages([
                'scores' => 'Total bobot penilaian harus 100 (saat ini '.number_format($total, 2, ',', '.').').',
            ]);
        }

        DB::transaction(function () use ($applicant, $scores, $actor): void {
            $applicant->scores()->delete();

            foreach ($scores as $row) {
                if (blank($row['criterion'] ?? null)) {
                    continue;
                }

                $applicant->scores()->create([
                    'criterion' => $row['criterion'],
                    'weight' => $row['weight'],
                    'score' => $row['score'],
                    'notes' => $row['notes'] ?? null,
                    'scored_by' => $actor->id,
                ]);
            }

            $applicant->load('scores');
            $applicant->update([
                'final_score' => $applicant->scores->isEmpty() ? null : $applicant->calculateFinalScore(),
            ]);
        });
    }

    public function transition(Applicant $applicant, string $status, ?string $note, User $actor): Applicant
    {
        $allowed = [
            'baru' => ['seleksi_administrasi', 'ditolak'],
            'seleksi_administrasi' => ['wawancara', 'ditolak'],
            'wawancara' => ['dipertimbangkan', 'ditolak'],
            'dipertimbangkan' => ['ditolak'],
        ];

        if (! in_array($status, $allowed[$applicant->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Perubahan status dari '.$applicant->status.' ke '.$status.' tidak diizinkan.',
            ]);
        }

        if ($status === 'ditolak' && trim((string) $note) === '') {
            throw ValidationException::withMessages(['note' => 'Alasan penolakan wajib diisi.']);
        }

        $applicant->update([
            'status' => $status,
            'notes' => $this->appendNote($applicant->notes, $note, $actor),
        ]);

        return $applicant->fresh();
    }

    public function decide(Applicant $applicant, string $decision, string $reason, User $actor): Applicant
    {
        if (! in_array($decision, ['diterima', 'ditolak'], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan tidak valid.']);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan keputusan wajib diisi.']);
        }

        if ($decision === 'diterima' && $applicant->status !== 'dipertimbangkan') {
            throw ValidationException::withMessages([
                'decision' => 'Pelamar harus berada pada tahap dipertimbangkan sebelum diterima.',
            ]);
        }

        if (in_array($applicant->status, ['diterima', 'ditolak'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Keputusan akhir untuk pelamar ini sudah ditetapkan.',
            ]);
        }

        $applicant->update([
            'status' => $decision,
            'notes' => $this->appendNote($applicant->notes, $reason, $actor),
        ]);

        return $applicant->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function convert(Applicant $applicant, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($applicant, $data, $actor): Employee {
            $applicant = Applicant::query()->lockForUpdate()->findOrFail($applicant->id);

            if (! $applicant->canBeConverted()) {
                throw ValidationException::withMessages([
                    'applicant' => 'Hanya pelamar berstatus diterima dan belum dikonversi yang dapat diproses.',
                ]);
            }

            if ($applicant->nik) {
                $hash = hash('sha256', $applicant->nik);
                if (Employee::query()->where('nik_hash', $hash)->exists()) {
                    throw ValidationException::withMessages([
                        'applicant' => 'NIK pelamar sudah terdaftar sebagai pegawai.',
                    ]);
                }
            }

            $employee = $this->employees->create([
                'nip' => $data['nip'] ?? null,
                'full_name' => $applicant->full_name,
                'nick_name' => $data['nick_name'] ?? null,
                'nik' => $applicant->nik,
                'birth_place' => $applicant->birth_place,
                'birth_date' => $applicant->birth_date?->toDateString(),
                'gender' => $applicant->gender ?? $data['gender'] ?? 'laki-laki',
                'religion' => $data['religion'] ?? null,
                'marital_status' => $data['marital_status'] ?? 'lajang',
                'address' => $applicant->address,
                'phone' => $applicant->phone,
                'email' => $applicant->email,
                'education_id' => $applicant->education_id,
                'dependents_count' => $data['dependents_count'] ?? 0,
                'org_unit_id' => $data['org_unit_id'],
                'position_id' => $data['position_id'],
                'work_location_id' => $data['work_location_id'] ?? null,
                'direct_supervisor_id' => $data['direct_supervisor_id'] ?? null,
                'employee_category' => $data['employee_category'],
                'employment_status' => $data['employment_status'] ?? 'aktif',
                'hire_date' => $data['hire_date'],
                'probation_end_date' => $data['probation_end_date'] ?? null,
                'is_active' => true,
            ], $actor);

            $applicant->update(['converted_employee_id' => $employee->id]);

            return $employee;
        });
    }

    protected function appendNote(?string $existing, ?string $note, User $actor): ?string
    {
        if (trim((string) $note) === '') {
            return $existing;
        }

        $line = '['.Carbon::now('Asia/Jakarta')->format('d-m-Y H:i').' — '.$actor->name.'] '.trim($note);

        return trim(($existing ? $existing."\n" : '').$line);
    }
}
