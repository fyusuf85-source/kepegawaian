<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(private readonly NumberSequenceService $sequences)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($data, $actor): Employee {
            $payroll = [
                'education_id' => $data['education_id'] ?? null,
                'dependents_count' => $data['dependents_count'] ?? 0,
            ];
            unset($data['education_id'], $data['dependents_count']);

            if (blank($data['nip'] ?? null)) {
                $data['nip'] = $this->sequences->next('nip');
            }

            $data['created_by'] = $actor->id;
            $data['is_active'] ??= true;

            /** @var Employee $employee */
            $employee = Employee::create($data);

            $employee->payrollProfile()->create($payroll + [
                'effective_from' => $employee->hire_date ?? now('Asia/Jakarta')->toDateString(),
                'updated_by' => $actor->id,
            ]);

            if ($employee->org_unit_id && $employee->position_id) {
                $employee->assignments()->create([
                    'org_unit_id' => $employee->org_unit_id,
                    'position_id' => $employee->position_id,
                    'direct_supervisor_id' => $employee->direct_supervisor_id,
                    'work_location_id' => $employee->work_location_id,
                    'effective_from' => $employee->hire_date ?? now('Asia/Jakarta')->toDateString(),
                    'reason' => 'Penempatan awal',
                    'created_by' => $actor->id,
                ]);
            }

            $employee->statusHistories()->create([
                'status' => $employee->employment_status ?? 'aktif',
                'effective_date' => $employee->hire_date ?? now('Asia/Jakarta')->toDateString(),
                'reason' => 'Data awal pegawai',
                'created_by' => $actor->id,
            ]);

            return $employee;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor): Employee {
            $oldUnit = $employee->org_unit_id;
            $oldPosition = $employee->position_id;

            $employee->fill($data)->save();

            $structureChanged = $oldUnit !== $employee->org_unit_id
                || $oldPosition !== $employee->position_id;

            if ($structureChanged && $employee->org_unit_id && $employee->position_id) {
                $today = Carbon::now('Asia/Jakarta')->toDateString();

                $employee->assignments()
                    ->whereNull('effective_to')
                    ->update(['effective_to' => Carbon::now('Asia/Jakarta')->subDay()->toDateString()]);

                $employee->assignments()->create([
                    'org_unit_id' => $employee->org_unit_id,
                    'position_id' => $employee->position_id,
                    'direct_supervisor_id' => $employee->direct_supervisor_id,
                    'work_location_id' => $employee->work_location_id,
                    'effective_from' => $today,
                    'reason' => 'Perubahan struktur dari master pegawai',
                    'created_by' => $actor->id,
                ]);
            }

            return $employee;
        });
    }
}
