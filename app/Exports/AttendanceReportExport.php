<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private int $month, private int $year) {}

    public function collection(): \Illuminate\Support\Collection
    {
        return Employee::query()
            ->where('is_active', true)
            ->withCount([
                'attendances as hadir' => fn ($q) => $q->whereMonth('work_date', $this->month)->whereYear('work_date', $this->year)->whereIn('status', ['hadir', 'terlambat', 'pulang_awal']),
                'attendances as terlambat' => fn ($q) => $q->whereMonth('work_date', $this->month)->whereYear('work_date', $this->year)->where('status', 'terlambat'),
                'attendances as izin' => fn ($q) => $q->whereMonth('work_date', $this->month)->whereYear('work_date', $this->year)->whereIn('status', ['izin', 'sakit', 'dispensasi', 'libur']),
                'attendances as alpa' => fn ($q) => $q->whereMonth('work_date', $this->month)->whereYear('work_date', $this->year)->where('status', 'tidak_hadir'),
            ])
            ->orderBy('full_name')
            ->get();
    }

    public function headings(): array
    {
        return ['NIP', 'Nama Lengkap', 'Hadir', 'Terlambat', 'Izin/Sakit/Libur', 'Alpa'];
    }

    /** @param Employee $row */
    public function map($row): array
    {
        return [$row->nip, $row->full_name, $row->hadir, $row->terlambat, $row->izin, $row->alpa];
    }
}
