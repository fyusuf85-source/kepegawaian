<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'NIP', 'Nama Lengkap', 'NIK', 'Jenis Kelamin', 'Tempat Lahir',
            'Tanggal Lahir', 'Telepon', 'Email', 'Kategori', 'Jenis Khidmah',
            'Status Kepegawaian', 'Unit', 'Jabatan', 'Lokasi Kerja',
            'Tanggal Masuk', 'Pendidikan Terakhir', 'Status Pajak', 'Jumlah Tanggungan',
        ];
    }

    /**
     * @param  Employee  $employee
     */
    public function map($employee): array
    {
        $highest = $employee->educations
            ->sortByDesc(fn ($education) => $education->education?->level_order ?? 0)
            ->first();

        return [
            $employee->nip,
            $employee->full_name,
            $employee->nik,
            $employee->gender,
            $employee->birth_place,
            $employee->birth_date?->format('d-m-Y'),
            $employee->phone,
            $employee->email,
            $employee->employee_category,
            $employee->khidmah_type,
            $employee->employment_status,
            $employee->orgUnit?->name,
            $employee->position?->name,
            $employee->workLocation?->name,
            $employee->hire_date?->format('d-m-Y'),
            $highest?->education?->name,
            $employee->payrollProfile?->tax_status,
            $employee->payrollProfile?->dependents_count,
        ];
    }
}
