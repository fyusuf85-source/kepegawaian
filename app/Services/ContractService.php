<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly DocumentPdfService $documents,
        private readonly NotificationService $notifications
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): EmploymentContract
    {
        return DB::transaction(function () use ($data, $actor): EmploymentContract {
            $data['contract_number'] = filled($data['contract_number'] ?? null)
                ? $data['contract_number']
                : $this->sequences->next('contract');
            $data['status'] = $data['status'] ?? 'draf';
            $data['created_by'] = $actor->id;

            return EmploymentContract::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function renew(EmploymentContract $previous, array $data, User $actor): EmploymentContract
    {
        $data['employee_id'] = $previous->employee_id;
        $data['previous_contract_id'] = $previous->id;
        $data['start_date'] = $data['start_date']
            ?? ($previous->end_date?->copy()->addDay()->toDateString() ?? now('Asia/Jakarta')->toDateString());
        $data['status'] = 'draf';

        return $this->create($data, $actor);
    }

    public function generateDocument(EmploymentContract $contract, User $actor): string
    {
        $contract->loadMissing('employee.orgUnit', 'employee.position');

        $path = $this->documents->generate(
            'KONTRAK-UMUM',
            [
                'nomor' => $contract->contract_number,
                'nama_pegawai' => $contract->employee?->full_name,
                'nip' => $contract->employee?->nip,
                'jabatan' => $contract->employee?->position?->name,
                'unit' => $contract->employee?->orgUnit?->name,
                'jenis_kontrak' => strtoupper($contract->contract_type),
                'tanggal_mulai' => $contract->start_date?->format('d-m-Y'),
                'tanggal_selesai' => $contract->end_date?->format('d-m-Y') ?? 'tidak berbatas waktu',
                'tanggal_probation' => $contract->probation_end_date?->format('d-m-Y') ?? '—',
                'tanggal_ttd' => $contract->signed_date?->format('d-m-Y') ?? '—',
                'kota' => 'Sidoarjo',
                'tanggal_cetak' => now('Asia/Jakarta')->format('d-m-Y'),
            ],
            'kontrak',
            $contract->contract_number.'-'.$contract->employee?->nip
        );

        $contract->update(['document_path' => $path]);

        return $path;
    }

    /**
     * Kirim pengingat H-30/H-14/H-7 dan perbarui status kontrak.
     *
     * @return array{reminded:int,expiring:int,ended:int}
     */
    public function sendReminders(?CarbonInterface $at = null): array
    {
        $today = Carbon::instance($at ?? now('Asia/Jakarta'))->timezone('Asia/Jakarta')->startOfDay();
        $stats = ['reminded' => 0, 'expiring' => 0, 'ended' => 0];

        $contracts = EmploymentContract::query()
            ->whereIn('status', ['aktif', 'akan_berakhir'])
            ->whereNotNull('end_date')
            ->with('employee.orgUnit')
            ->get();

        foreach ($contracts as $contract) {
            $end = Carbon::parse($contract->end_date->toDateString(), 'Asia/Jakarta')->startOfDay();
            $days = (int) $today->diffInDays($end, false);

            if ($days < 0) {
                $contract->update(['status' => 'berakhir']);
                $stats['ended']++;

                continue;
            }

            if ($days <= 30 && $contract->status !== 'akan_berakhir') {
                $contract->update(['status' => 'akan_berakhir']);
                $stats['expiring']++;
            }

            if (! in_array($days, [30, 14, 7], true)) {
                continue;
            }

            $recipients = $this->notifications->usersWithRole('hrd');
            $unitId = $contract->employee?->org_unit_id;

            $recipients = $recipients->merge(
                $this->notifications->usersWithRole('kepala_bagian')
                    ->filter(fn (User $user) => $unitId !== null
                        && in_array($unitId, $user->scopedOrgUnitIds(), true))
            );

            $sent = $this->notifications->sendToUsers(
                $recipients,
                'contract_reminder',
                'Kontrak akan berakhir',
                "Kontrak {$contract->contract_number} ({$contract->employee?->full_name}) berakhir dalam {$days} hari.",
                [
                    'contract_id' => $contract->id,
                    'employee_id' => $contract->employee_id,
                    'end_date' => $contract->end_date->toDateString(),
                    'days_left' => $days,
                    'dedupe_key' => "contract:{$contract->id}:h{$days}",
                ]
            );

            $stats['reminded'] += $sent;
        }

        return $stats;
    }
}
