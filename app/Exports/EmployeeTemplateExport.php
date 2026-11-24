<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [[
            '',
            'CONTOH — Hapus baris ini',
            '',
            'L',
            'Sidoarjo',
            '01-01-1990',
            '081234567890',
            '',
            'tetap',
            '',
            'aktif',
            'PIS-YAYASAN',
            'GURU',
            'PUSAT',
            '01-07-2026',
            'S1',
            'TK/0',
            '0',
        ]];
    }

    public function headings(): array
    {
        return [
            'NIP', 'Nama Lengkap', 'NIK', 'Jenis Kelamin', 'Tempat Lahir',
            'Tanggal Lahir', 'Telepon', 'Email', 'Kategori', 'Jenis Khidmah',
            'Status Kepegawaian', 'Kode Unit', 'Kode Jabatan', 'Kode Lokasi',
            'Tanggal Masuk', 'Kode Pendidikan', 'Status Pajak', 'Jumlah Tanggungan',
        ];
    }
}
