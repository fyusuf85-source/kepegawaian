<?php

namespace App\Support;

use App\Models\DocumentTemplate;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\OrgUnit;
use App\Models\Position;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Model;

class MasterData
{
    /**
     * Definisi CRUD master data sederhana.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'org-units' => [
                'title' => 'Unit Organisasi',
                'singular' => 'Unit Organisasi',
                'model' => OrgUnit::class,
                'permission' => 'org.manage',
                'view_permission' => 'org.view',
                'route' => 'master.org-units',
                'searchable' => ['code', 'name'],
                'with' => ['parent:id,name'],
                'order_by' => ['level' => 'asc', 'sort_order' => 'asc', 'name' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'type', 'label' => 'Tipe'],
                    ['key' => 'parent.name', 'label' => 'Induk'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Unit', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => [
                        'yayasan' => 'Yayasan',
                        'pimpinan' => 'Pimpinan',
                        'bidang' => 'Bidang',
                        'bagian' => 'Bagian',
                        'seksi' => 'Seksi',
                        'unit_pendidikan' => 'Unit Pendidikan',
                    ]],
                    ['name' => 'parent_id', 'label' => 'Unit Induk', 'type' => 'parent_org_unit', 'nullable' => true],
                    ['name' => 'leader_employee_id', 'label' => 'Kepala Unit', 'type' => 'employee', 'nullable' => true],
                    ['name' => 'address', 'label' => 'Alamat', 'type' => 'text', 'nullable' => true],
                    ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text', 'nullable' => true],
                    ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'nullable' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
                'delete_guard' => function (OrgUnit $unit): ?string {
                    if ($unit->children()->exists()) {
                        return 'Unit masih memiliki sub-unit. Pindahkan atau hapus sub-unit terlebih dahulu.';
                    }
                    if ($unit->employees()->exists()) {
                        return 'Unit masih memiliki pegawai. Pindahkan pegawai terlebih dahulu.';
                    }

                    return null;
                },
            ],

            'positions' => [
                'title' => 'Jabatan',
                'singular' => 'Jabatan',
                'model' => Position::class,
                'permission' => 'org.manage',
                'view_permission' => 'org.view',
                'route' => 'master.positions',
                'searchable' => ['code', 'name'],
                'with' => ['orgUnit:id,name'],
                'order_by' => ['name' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama Jabatan'],
                    ['key' => 'orgUnit.name', 'label' => 'Unit'],
                    ['key' => 'level', 'label' => 'Level'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Jabatan', 'type' => 'text', 'required' => true],
                    ['name' => 'org_unit_id', 'label' => 'Unit', 'type' => 'org_unit', 'nullable' => true],
                    ['name' => 'level', 'label' => 'Level', 'type' => 'number', 'nullable' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
                'delete_guard' => fn (Position $position): ?string => $position->employees()->exists()
                    ? 'Jabatan masih dipakai pegawai.'
                    : null,
            ],

            'work-locations' => [
                'title' => 'Lokasi Kerja',
                'singular' => 'Lokasi Kerja',
                'model' => WorkLocation::class,
                'permission' => 'work_location.manage',
                'view_permission' => 'org.view',
                'route' => 'master.work-locations',
                'searchable' => ['code', 'name', 'address'],
                'with' => ['orgUnit:id,name'],
                'order_by' => ['name' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'latitude', 'label' => 'Latitude'],
                    ['key' => 'longitude', 'label' => 'Longitude'],
                    ['key' => 'radius_m', 'label' => 'Radius (m)'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Lokasi', 'type' => 'text', 'required' => true],
                    ['name' => 'org_unit_id', 'label' => 'Unit', 'type' => 'org_unit', 'nullable' => true],
                    ['name' => 'address', 'label' => 'Alamat', 'type' => 'text', 'nullable' => true],
                    ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'decimal', 'required' => true, 'help' => '-90 s.d. 90'],
                    ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'decimal', 'required' => true, 'help' => '-180 s.d. 180'],
                    ['name' => 'radius_m', 'label' => 'Radius (meter)', 'type' => 'number', 'required' => true],
                    ['name' => 'accuracy_threshold_m', 'label' => 'Ambang Akurasi GPS (meter)', 'type' => 'number', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
                'delete_guard' => fn (WorkLocation $location): ?string => $location->employees()->exists()
                    ? 'Lokasi masih dipakai pegawai.'
                    : null,
            ],

            'educations' => [
                'title' => 'Jenjang Pendidikan',
                'singular' => 'Jenjang Pendidikan',
                'model' => Education::class,
                'permission' => 'org.manage',
                'view_permission' => 'org.view',
                'route' => 'master.educations',
                'searchable' => ['code', 'name'],
                'with' => [],
                'order_by' => ['level_order' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'level_order', 'label' => 'Urutan'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Jenjang', 'type' => 'text', 'required' => true],
                    ['name' => 'level_order', 'label' => 'Urutan Jenjang', 'type' => 'number', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
            ],

            'work-shifts' => [
                'title' => 'Shift Kerja',
                'singular' => 'Shift Kerja',
                'model' => WorkShift::class,
                'permission' => 'schedule.manage',
                'view_permission' => 'schedule.view',
                'route' => 'master.work-shifts',
                'searchable' => ['code', 'name'],
                'with' => [],
                'order_by' => ['start_time' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'start_time', 'label' => 'Mulai', 'type' => 'time'],
                    ['key' => 'end_time', 'label' => 'Selesai', 'type' => 'time'],
                    ['key' => 'break_minutes', 'label' => 'Istirahat (menit)'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Shift', 'type' => 'text', 'required' => true],
                    ['name' => 'start_time', 'label' => 'Jam Mulai', 'type' => 'time', 'required' => true],
                    ['name' => 'end_time', 'label' => 'Jam Selesai', 'type' => 'time', 'required' => true],
                    ['name' => 'break_minutes', 'label' => 'Istirahat (menit)', 'type' => 'number', 'required' => true],
                    ['name' => 'late_tolerance_minutes', 'label' => 'Toleransi Terlambat (menit)', 'type' => 'number', 'required' => true],
                    ['name' => 'early_leave_tolerance_minutes', 'label' => 'Toleransi Pulang Cepat (menit)', 'type' => 'number', 'required' => true],
                    ['name' => 'is_cross_midnight', 'label' => 'Melewati Tengah Malam', 'type' => 'checkbox'],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
            ],

            'holidays' => [
                'title' => 'Hari Libur',
                'singular' => 'Hari Libur',
                'model' => Holiday::class,
                'permission' => 'holiday.manage',
                'view_permission' => 'schedule.view',
                'route' => 'master.holidays',
                'searchable' => ['name'],
                'with' => [],
                'order_by' => ['date' => 'desc'],
                'columns' => [
                    ['key' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'type', 'label' => 'Jenis'],
                    ['key' => 'is_paid', 'label' => 'Berbayar', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'date', 'label' => 'Tanggal', 'type' => 'date', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Hari Libur', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'Jenis', 'type' => 'select', 'required' => true, 'options' => [
                        'nasional' => 'Nasional',
                        'pesantren' => 'Pesantren',
                    ]],
                    ['name' => 'is_paid', 'label' => 'Berbayar', 'type' => 'checkbox'],
                ],
            ],

            'leave-types' => [
                'title' => 'Jenis Izin & Cuti',
                'singular' => 'Jenis Izin',
                'model' => LeaveType::class,
                'permission' => 'leave.type.manage',
                'view_permission' => 'leave.type.manage',
                'route' => 'master.leave-types',
                'searchable' => ['code', 'name'],
                'with' => [],
                'order_by' => ['sort_order' => 'asc', 'name' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'requires_document', 'label' => 'Wajib Dokumen', 'type' => 'boolean'],
                    ['key' => 'affects_balance', 'label' => 'Mengurangi Saldo', 'type' => 'boolean'],
                    ['key' => 'max_days_per_year', 'label' => 'Maks. Hari/Tahun'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Jenis', 'type' => 'text', 'required' => true],
                    ['name' => 'requires_document', 'label' => 'Wajib Dokumen', 'type' => 'checkbox'],
                    ['name' => 'document_required_after_days', 'label' => 'Dokumen Wajib Setelah (hari)', 'type' => 'number', 'nullable' => true],
                    ['name' => 'affects_balance', 'label' => 'Mengurangi Saldo Cuti', 'type' => 'checkbox'],
                    ['name' => 'is_paid', 'label' => 'Berbayar', 'type' => 'checkbox'],
                    ['name' => 'max_days_per_year', 'label' => 'Maksimum Hari per Tahun', 'type' => 'number', 'nullable' => true],
                    ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'nullable' => true],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
            ],

            'document-templates' => [
                'title' => 'Template Dokumen',
                'singular' => 'Template Dokumen',
                'model' => DocumentTemplate::class,
                'permission' => 'letter.manage',
                'view_permission' => 'letter.view',
                'route' => 'master.document-templates',
                'searchable' => ['code', 'name'],
                'with' => [],
                'order_by' => ['name' => 'asc'],
                'columns' => [
                    ['key' => 'code', 'label' => 'Kode'],
                    ['key' => 'name', 'label' => 'Nama'],
                    ['key' => 'doc_type', 'label' => 'Jenis'],
                    ['key' => 'is_active', 'label' => 'Status', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text', 'required' => true, 'unique' => true],
                    ['name' => 'name', 'label' => 'Nama Template', 'type' => 'text', 'required' => true],
                    ['name' => 'doc_type', 'label' => 'Jenis', 'type' => 'select', 'required' => true, 'options' => [
                        'sk' => 'SK',
                        'kontrak' => 'Kontrak',
                        'surat' => 'Surat',
                        'slip' => 'Slip',
                    ]],
                    ['name' => 'body', 'label' => 'Isi Template (HTML)', 'type' => 'textarea', 'required' => true, 'rows' => 12],
                    ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolve(string $resource): array
    {
        $definitions = static::definitions();

        abort_unless(isset($definitions[$resource]), 404, 'Master data tidak ditemukan.');

        return $definitions[$resource];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function selectOptions(array $def, ?Model $record = null): array
    {
        $options = [];

        foreach ($def['fields'] as $field) {
            if ($field['type'] === 'org_unit') {
                $options[$field['name']] = OrgUnit::query()
                    ->orderBy('level')->orderBy('name')->pluck('name', 'id')->all();
            }

            if ($field['type'] === 'parent_org_unit') {
                $query = OrgUnit::query()->orderBy('level')->orderBy('sort_order')->orderBy('name');
                if ($record instanceof OrgUnit) {
                    $excluded = OrgUnit::descendantIds([$record->id]);
                    $query->whereNotIn('id', $excluded);
                }
                $options[$field['name']] = $query->pluck('name', 'id')->all();
            }

            if ($field['type'] === 'employee') {
                $options[$field['name']] = Employee::query()
                    ->where('is_active', true)
                    ->orderBy('full_name')
                    ->pluck('full_name', 'id')
                    ->all();
            }
        }

        return $options;
    }
}
