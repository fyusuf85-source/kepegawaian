<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayslipGenerationService
{
    public function __construct(private readonly PayrollPdfService $pdf, private readonly AuditLogger $audit) {}

    public function generate(PayrollPeriod $period): int
    {
        if (! $period->isLocked()) {
            throw ValidationException::withMessages(['period' => 'Slip resmi hanya dapat diterbitkan setelah periode payroll disetujui dan dikunci.']);
        }

        $run = $period->runs()->where('status', 'final')->latest('version')->firstOrFail();

        return DB::transaction(function () use ($period, $run) {
            $generated = 0;
            foreach ($run->employees()->with('employee:id,nip,full_name')->get() as $row) {
                if (empty($row->employee->nip)) continue;
                $path = $this->pdf->generateOne($row);
                Payslip::query()->updateOrCreate(
                    ['payroll_period_id' => $period->id, 'employee_id' => $row->employee_id],
                    [
                        'payroll_run_id' => $run->id,
                        'gross' => $row->gross,
                        'total_deduction' => $row->total_deduction,
                        'net' => $row->net,
                        'file_path' => $path,
                        'generated_at' => now(),
                    ]
                );
                $generated++;
            }
            $this->audit->recordRaw('payslip.generated', Payslip::class, null, null, ['period_id' => $period->id, 'count' => $generated]);
            return $generated;
        });
    }
}
