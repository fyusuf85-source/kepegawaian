<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendancePeriodSummary;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(private readonly AuditLogger $audit, private readonly PayrollCalculator $calculator) {}

    public function snapshot(PayrollPeriod $period, User $user): int
    {
        if ($period->isLocked()) throw ValidationException::withMessages(['period' => 'Periode payroll sudah dikunci.']);

        return DB::transaction(function () use ($period, $user) {
            $employees = Employee::query()->where('is_active', true)->get(['id']);
            foreach ($employees as $employee) {
                $rows = Attendance::query()->where('employee_id', $employee->id)->whereBetween('work_date', [$period->start_date, $period->end_date])->get();
                $counts = $rows->countBy('status');
                AttendancePeriodSummary::query()->updateOrCreate(
                    ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
                    [
                        'work_days' => $rows->count(), 'present_days' => ($counts['hadir'] ?? 0) + ($counts['terlambat'] ?? 0) + ($counts['pulang_awal'] ?? 0),
                        'late_days' => $rows->where('late_minutes', '>', 0)->count(), 'total_late_minutes' => $rows->sum('late_minutes'),
                        'early_leave_days' => $rows->where('early_leave_minutes', '>', 0)->count(), 'total_early_minutes' => $rows->sum('early_leave_minutes'),
                        'absent_days' => $counts['tidak_hadir'] ?? 0, 'paid_leave_days' => ($counts['izin'] ?? 0) + ($counts['sakit'] ?? 0),
                        'unpaid_leave_days' => 0, 'holiday_days' => $counts['libur'] ?? 0, 'special_days' => $counts['dispensasi'] ?? 0,
                        'is_locked' => false, 'generated_at' => now(), 'generated_by' => $user->id,
                    ]
                );
            }
            $period->update(['status' => 'dihitung']);
            $this->audit->recordRaw('payroll.snapshot.generated', PayrollPeriod::class, $period->id, null, ['employee_count' => $employees->count()]);
            return $employees->count();
        });
    }

    public function calculateSkeleton(PayrollPeriod $period, User $user): PayrollRun
    {
        if ($period->isLocked()) throw ValidationException::withMessages(['period' => 'Periode payroll sudah dikunci.']);
        if (! AttendancePeriodSummary::query()->where('payroll_period_id', $period->id)->exists()) throw ValidationException::withMessages(['period' => 'Buat snapshot absensi sebelum menghitung payroll.']);

        return DB::transaction(function () use ($period, $user) {
            PayrollRun::query()
                ->where('payroll_period_id', $period->id)
                ->whereIn('status', ['draf', 'final'])
                ->update(['status' => 'dibatalkan']);

            $version = ((int) PayrollRun::query()->where('payroll_period_id', $period->id)->max('version')) + 1;
            $run = PayrollRun::query()->create(['payroll_period_id' => $period->id, 'version' => $version, 'status' => 'draf', 'calculated_by' => $user->id, 'calculated_at' => now()]);
            $summaries = AttendancePeriodSummary::query()->where('payroll_period_id', $period->id)->get();
            foreach ($summaries as $summary) {
                $runEmployee = PayrollRunEmployee::query()->create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $summary->employee_id,
                    'attendance_summary_id' => $summary->id,
                    'status' => 'dihitung',
                ]);
                foreach ($this->calculator->calculate($period, $runEmployee) as $detail) {
                    PayrollDetail::query()->create(['payroll_run_employee_id' => $runEmployee->id] + $detail);
                }
                if ($runEmployee->notes) {
                    $runEmployee->update(['status' => 'anomali']);
                }
                $this->applyApprovedAdjustments($period, $run, $runEmployee);
            }
            $this->refreshRunTotals($run);
            $run->update(['employee_count' => $summaries->count()]);
            $period->update(['status' => 'dihitung']);
            $this->audit->recordRaw('payroll.run.created', PayrollRun::class, $run->id, null, ['version' => $version, 'employee_count' => $summaries->count()]);
            return $run;
        });
    }

    private function applyApprovedAdjustments(PayrollPeriod $period, PayrollRun $run, PayrollRunEmployee $runEmployee): void
    {
        $adjustments = PayrollAdjustment::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $runEmployee->employee_id)
            ->where('status', 'disetujui')
            ->whereNull('applied_run_id')
            ->get();

        foreach ($adjustments as $adjustment) {
            $component = $adjustment->component;
            if (! $component) continue;
            PayrollDetail::query()->create([
                'payroll_run_employee_id' => $runEmployee->id,
                'component_id' => $component->id,
                'type' => $adjustment->type,
                'quantity' => 1,
                'unit_rate' => $adjustment->amount,
                'amount' => $adjustment->type === 'potongan' ? -abs($adjustment->amount) : abs($adjustment->amount),
                'formula_note' => 'Penyesuaian disetujui: '.$adjustment->reason,
                'source_type' => 'manual_adjustment',
                'source_id' => $adjustment->id,
            ]);
            $adjustment->update(['applied_run_id' => $run->id]);
        }
    }

    private function refreshRunTotals(PayrollRun $run): void
    {
        $rows = $run->employees()->with('details')->get();
        $gross = $deduction = $employer = 0.0;
        foreach ($rows as $row) {
            $income = (float) $row->details->where('type', 'penghasilan')->sum('amount');
            $cuts = abs((float) $row->details->where('type', 'potongan')->sum('amount'));
            $employerForEmployee = (float) $row->details->where('type', 'iuran_pemberi')->sum('amount');
            $row->update(['gross' => $income, 'total_deduction' => $cuts, 'employer_contribution' => $employerForEmployee, 'net' => $income - $cuts]);
            $gross += $income; $deduction += $cuts; $employer += $employerForEmployee;
        }
        $run->update(['total_gross' => $gross, 'total_deduction' => $deduction, 'total_net' => $gross - $deduction, 'total_employer_contribution' => $employer]);
    }

    public function lock(PayrollPeriod $period, User $user): void
    {
        if ($period->isLocked()) throw ValidationException::withMessages(['period' => 'Periode sudah dikunci.']);
        $approved = \App\Models\ApprovalInstance::query()->where('subject_type', $period->getMorphClass())->where('subject_id', $period->id)->where('status', 'disetujui')->exists();
        if (! $approved) throw ValidationException::withMessages(['period' => 'Periode wajib mendapat persetujuan HRD, Keuangan, dan Pimpinan sebelum dikunci.']);
        $run = $period->runs()->where('status', 'final')->latest('version')->first();
        if (! $run) throw ValidationException::withMessages(['period' => 'Periode hanya dapat dikunci setelah payroll disetujui dan run berstatus final.']);
        if (! $period->summaries()->exists()) throw ValidationException::withMessages(['period' => 'Snapshot absensi wajib tersedia sebelum periode dikunci.']);
        $period->summaries()->update(['is_locked' => true]);
        Attendance::query()->whereBetween('work_date', [$period->start_date, $period->end_date])->update(['is_locked' => true, 'payroll_period_id' => $period->id]);
        $period->update(['status' => 'dikunci', 'locked_at' => now(), 'locked_by' => $user->id]);
        $this->audit->recordRaw('payroll.period.locked', PayrollPeriod::class, $period->id);
    }
}
