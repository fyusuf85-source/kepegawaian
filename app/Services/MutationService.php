<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Mutation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MutationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Mutation
    {
        return DB::transaction(function () use ($data, $actor): Mutation {
            $employee = Employee::query()->findOrFail($data['employee_id']);

            if (blank($data['to_org_unit_id'] ?? null)
                && blank($data['to_position_id'] ?? null)
                && blank($data['to_supervisor_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'to_org_unit_id' => 'Isi minimal satu tujuan perubahan (unit, jabatan, atau atasan).',
                ]);
            }

            $data += [
                'from_org_unit_id' => $employee->org_unit_id,
                'from_position_id' => $employee->position_id,
                'from_supervisor_id' => $employee->direct_supervisor_id,
                'status' => 'draf',
                'requested_by' => $actor->id,
            ];

            return Mutation::create($data);
        });
    }

    public function apply(Mutation $mutation, User $actor): Mutation
    {
        return DB::transaction(function () use ($mutation, $actor): Mutation {
            $mutation = Mutation::query()->lockForUpdate()->findOrFail($mutation->id);

            if ($mutation->status !== 'disetujui') {
                throw ValidationException::withMessages([
                    'mutation' => 'Mutasi hanya dapat diterapkan setelah disetujui.',
                ]);
            }

            $employee = Employee::query()->lockForUpdate()->findOrFail($mutation->employee_id);
            $effectiveDate = $mutation->effective_date->copy()->startOfDay();

            $active = $employee->assignments()->whereNull('effective_to')->get();

            foreach ($active as $assignment) {
                if ($assignment->effective_from->gte($effectiveDate)) {
                    throw ValidationException::withMessages([
                        'mutation' => 'Tanggal efektif harus setelah tanggal mulai penempatan aktif.',
                    ]);
                }
            }

            foreach ($active as $assignment) {
                $assignment->update([
                    'effective_to' => $effectiveDate->copy()->subDay()->toDateString(),
                ]);
            }

            $newUnit = $mutation->to_org_unit_id ?? $employee->org_unit_id;
            $newPosition = $mutation->to_position_id ?? $employee->position_id;
            $newSupervisor = $mutation->to_supervisor_id ?? $employee->direct_supervisor_id;

            if (! $newUnit || ! $newPosition) {
                throw ValidationException::withMessages([
                    'mutation' => 'Unit dan jabatan tujuan wajib diketahui.',
                ]);
            }

            $employee->assignments()->create([
                'org_unit_id' => $newUnit,
                'position_id' => $newPosition,
                'direct_supervisor_id' => $newSupervisor,
                'work_location_id' => $employee->work_location_id,
                'effective_from' => $effectiveDate->toDateString(),
                'decision_number' => $mutation->decree?->number,
                'reason' => $mutation->reason,
                'created_by' => $actor->id,
            ]);

            $employee->update([
                'org_unit_id' => $newUnit,
                'position_id' => $newPosition,
                'direct_supervisor_id' => $newSupervisor,
            ]);

            $mutation->update([
                'status' => 'diterapkan',
                'approved_by' => $mutation->approved_by ?? $actor->id,
            ]);

            return $mutation->fresh();
        });
    }

    public function defaultEffectiveDate(): string
    {
        return Carbon::now('Asia/Jakarta')->addMonth()->startOfMonth()->toDateString();
    }
}
