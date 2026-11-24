<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentRate;
use App\Models\PayrollEducationBase;
use App\Models\PayrollParameter;
use App\Models\PayrollPeriod;
use App\Models\PayrollTransportBand;
use App\Models\PayrollRunEmployee;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PayrollCalculator
{
    public function __construct(private readonly BpjsCalculator $bpjsCalculator, private readonly Pph21Calculator $pph21Calculator) {}

    private const DAYS_PER_MONTH = 30;
    private const MINUTES_PER_MONTH = 14400;

    public function calculate(PayrollPeriod $period, PayrollRunEmployee $row): array
    {
        $row->loadMissing(['employee.payrollProfile.education', 'attendanceSummary']);
        $employee = $row->employee;
        $profile = $employee->payrollProfile;
        if (! $profile) throw ValidationException::withMessages(['payroll' => "Profil payroll {$employee->full_name} belum tersedia."]);

        $components = PayrollComponent::query()->where('is_active', true)->get()->keyBy('code');
        $parameters = PayrollParameter::query()->where('effective_from', '<=', $period->end_date)->where(function ($q) use ($period) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date); })->get()->keyBy('parameter_key');
        $status = $this->employmentStatus($employee);
        $summary = $row->attendanceSummary;
        $details = collect();

        $baseSalary = $this->baseSalary($employee, $profile, $status, $period);
        $dailyRate = $this->parameter($parameters, 'daily_rate_harian', 51000);
        $snackRate = $this->parameter($parameters, 'snack_per_day', 2500);
        if ($baseSalary > 0) $details->push($this->line($components, 'gaji_pokok', 'penghasilan', $baseSalary, 1, $baseSalary, 'Dasar gaji sesuai profil payroll.'));

        if (in_array($status, ['tetap', 'percobaan', 'khidmah_khusus'], true)) {
            $this->familyAllowances($details, $components, $profile, $parameters);
            $this->attendanceAllowance($details, $components, $employee, $summary, $parameters);
            $this->positionAndSpecialAllowances($details, $components, $employee, $profile);
            $this->transportAllowance($details, $components, $profile, $period);
            $this->snackAllowance($details, $components, $profile, $snackRate);
        }

        if ($status === 'harian') {
            $absenceMinutes = (int) ($summary?->absent_days ?? 0) * 480 + (int) ($summary?->total_late_minutes ?? 0) + (int) ($summary?->total_early_minutes ?? 0);
            $workDays = max(0, self::DAYS_PER_MONTH - ($absenceMinutes / 480));
            $amount = $dailyRate * $workDays;
            $details->push($this->line($components, 'gaji_harian', 'penghasilan', $amount, $workDays, $dailyRate, 'Rp'.number_format($dailyRate,0,',','.').' x (30 - menit tidak hadir/480).'));
            if ($profile->spouse_status && ! $profile->spouse_is_employee) {
                $riceAmount = $this->parameter($parameters, 'rice_allowance_per_member', 70000);
                $members = 2 + (int) $profile->child_count;
                $details->push($this->line($components, 'tunjangan_beras', 'penghasilan', $riceAmount * $members, $members, $riceAmount, 'Beras keluarga pegawai harian.'));
            }
        }
        if (in_array($status, ['harian', 'tetap', 'percobaan', 'khidmah_khusus'], true)) {
            $this->transportAllowance($details, $components, $profile, $period);
            $this->snackAllowance($details, $components, $profile, $snackRate);
        }

        if ($status === 'khidmah') {
            $khidmahRate = $this->parameter($parameters, 'khidmah_honorarium', 700000);
            $details->push($this->line($components, 'honorarium_khidmah', 'penghasilan', $khidmahRate, 1, $khidmahRate, 'Honorarium khidmah.'));
        }

        $baseIncome = (float) collect($details)->filter()->where('type', 'penghasilan')->sum('amount');
        $fullAttendance = $this->parameter($parameters, 'attendance_full_standard', 540000);
        $bpjsDetails = $this->bpjsCalculator->calculate($employee, $profile, $period, $parameters, $components, $baseIncome, $fullAttendance);
        foreach ($bpjsDetails as $detail) {
            $details->push($detail);
        }

        $employerBpjs = collect($bpjsDetails)->filter(fn ($d) => $d['type'] === 'iuran_pemberi')->sum('amount');
        $pphGross = $baseIncome + $employerBpjs;
        if ($pph21 = $this->pph21Calculator->calculate($employee, $profile, $pphGross, $components)) {
            $details->push($pph21);
        }

        $this->detectAnomalies($row, $details, $employee, $period);

        return $details->filter()->values()->all();
    }

    private function detectAnomalies(PayrollRunEmployee $row, Collection $details, Employee $employee, PayrollPeriod $period): void
    {
        $anomalies = [];
        $income = (float) $details->where('type', 'penghasilan')->sum('amount');

        // 1. New Hire / Termination (Potential prorata)
        if ($employee->hire_date && $employee->hire_date->between($period->start_date, $period->end_date)) {
            $anomalies[] = 'Pegawai baru masuk pada '.$employee->hire_date->format('d/m/Y').'. Cek prorata.';
        }
        if ($employee->termination_date && $employee->termination_date->between($period->start_date, $period->end_date)) {
            $anomalies[] = 'Pegawai keluar pada '.$employee->termination_date->format('d/m/Y').'. Cek prorata.';
        }

        // 2. No Base Salary
        if ($income <= 0) {
            $anomalies[] = 'Penghasilan nol. Cek profil payroll.';
        }

        // 3. High Late/Early Leave
        $summary = $row->attendanceSummary;
        if ($summary && $summary->total_late_minutes > 480) {
            $anomalies[] = 'Total keterlambatan tinggi ('.number_format($summary->total_late_minutes).' menit).';
        }

        if (! empty($anomalies)) {
            $row->update(['notes' => trim(($row->notes ? $row->notes."\n" : '').implode(' ', $anomalies))]);
        }
    }

    private function familyAllowances(Collection $details, Collection $components, object $profile, Collection $parameters): void
    {
        if (! $profile->spouse_status) return;
        if (! $profile->spouse_is_employee) {
            $spouse = $this->parameter($parameters, 'spouse_allowance', 200000);
            $details->push($this->line($components, 'tunjangan_pasangan', 'penghasilan', $spouse, 1, $spouse, 'Tunjangan pasangan.'));
        }
        $childRate = $this->parameter($parameters, 'child_allowance', 100000);
        $children = (int) $profile->child_count;
        if ($children) $details->push($this->line($components, 'tunjangan_anak', 'penghasilan', $children * $childRate, $children, $childRate, 'Tunjangan anak.'));
        $riceRate = $this->parameter($parameters, 'rice_allowance_per_member', 70000);
        $members = 1 + ($profile->spouse_is_employee ? 0 : 1) + $children;
        $details->push($this->line($components, 'tunjangan_beras', 'penghasilan', $members * $riceRate, $members, $riceRate, 'Beras anggota keluarga.'));
    }

    private function attendanceAllowance(Collection $details, Collection $components, Employee $employee, mixed $summary, Collection $parameters): void
    {
        $unitName = strtolower((string) $employee->orgUnit?->name);
        $isMsuSd = str_contains($unitName, 'msu') || str_contains($unitName, 'sd');
        $full = $this->parameter($parameters, $isMsuSd ? 'attendance_full_msu_sd' : 'attendance_full_standard', $isMsuSd ? 270000 : 540000);
        $minutesPerMonth = $this->parameter($parameters, 'minutes_per_month', self::MINUTES_PER_MONTH);
        $minutes = (int) (($summary?->total_late_minutes ?? 0) + ($summary?->total_early_minutes ?? 0));
        $amount = max(0, ceil($full - ($full / $minutesPerMonth * $minutes)));
        $details->push($this->line($components, 'tunjangan_kehadiran', 'penghasilan', $amount, max(0, $minutesPerMonth - $minutes), $full / $minutesPerMonth, 'Kehadiran penuh dikurangi menit terlambat/pulang awal.'));
    }

    private function positionAndSpecialAllowances(Collection $details, Collection $components, Employee $employee, object $profile): void
    {
        $position = $employee->position;
        if ($position && (float) ($position->allowance_amount ?? 0) > 0) {
            $amount = (float) $position->allowance_amount;
            $details->push($this->line($components, 'tunjangan_jabatan', 'penghasilan', $amount, 1, $amount, 'Tunjangan jabatan '.$position->name.'.'));
        }
        if ((float) $profile->special_allowance_amount > 0) {
            $amount = (float) $profile->special_allowance_amount;
            $details->push($this->line($components, 'tunjangan_istimewa', 'penghasilan', $amount, 1, $amount, 'Tunjangan istimewa.'));
        }
    }

    private function transportAllowance(Collection $details, Collection $components, object $profile, PayrollPeriod $period): void
    {
        $days = (int) $profile->transport_days;
        if ($days <= 0 || (float) $profile->transport_distance_km <= 1) return;
        $perDay = $this->transportRate((float) $profile->transport_distance_km, $period);
        if ($perDay <= 0) return;
        $details->push($this->line($components, 'transport', 'penghasilan', $perDay * $days, $days, $perDay, 'Transportasi jarak '.$profile->transport_distance_km.' km x '.$days.' hari.'));
    }

    private function snackAllowance(Collection $details, Collection $components, object $profile, float $rate): void
    {
        $days = (int) $profile->snack_days;
        if ($days <= 0) return;
        $details->push($this->line($components, 'uang_snack', 'penghasilan', $days * $rate, $days, $rate, 'Uang snack '.$days.' hari.'));
    }

    private function calculateTransportAmount(float $distanceKm, int $days, PayrollPeriod $period): float
    {
        return $this->transportRate($distanceKm, $period) * $days;
    }

    private function transportRate(float $distanceKm, PayrollPeriod $period): float
    {
        $band = PayrollTransportBand::query()
            ->where('effective_from', '<=', $period->end_date)
            ->where(function ($q) use ($period) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date); })
            ->where('min_km', '<=', $distanceKm)
            ->where(function ($q) use ($distanceKm) { $q->whereNull('max_km')->orWhere('max_km', '>=', $distanceKm); })
            ->orderByDesc('min_km')
            ->first();
        return $band ? (float) $band->amount : 0.0;
    }

    private function baseSalary(Employee $employee, object $profile, string $status, PayrollPeriod $period): float
    {
        if ($status === 'khidmah') return 0;
        if ($profile->last_salary_amount !== null && (float) $profile->last_salary_amount > 0) return (float) $profile->last_salary_amount;
        if ($profile->salary_basis_override !== null && (float) $profile->salary_basis_override > 0) return (float) $profile->salary_basis_override;
        $educationId = $profile->education_id;
        if ($educationId) {
            $base = PayrollEducationBase::query()
                ->where('education_id', $educationId)
                ->where('effective_from', '<=', $period->end_date)
                ->where(function ($q) use ($period) { $q->whereNull('effective_to')->orWhere('effective_to', '>=', $period->start_date); })
                ->orderByDesc('effective_from')
                ->first();
            if ($base) return (float) $base->base_amount;
        }
        throw ValidationException::withMessages(['payroll' => "Pendidikan/gaji pokok terakhir {$employee->full_name} belum dikonfigurasi."]);
    }

    private function parameter(Collection $parameters, string $key, float $fallback): float
    {
        $parameter = $parameters->get($key);
        return $parameter ? (float) $parameter->value : $fallback;
    }

    private function employmentStatus(Employee $employee): string
    {
        $value = strtolower((string) ($employee->employment_status ?? $employee->status ?? 'tetap'));
        return match (true) {
            str_contains($value, 'harian') => 'harian', str_contains($value, 'khidmah khusus') => 'khidmah_khusus',
            str_contains($value, 'khidmah') => 'khidmah', str_contains($value, 'percobaan') => 'percobaan', default => 'tetap',
        };
    }

    private function line(Collection $components, string $code, string $type, float $amount, float $quantity, float $rate, string $note): ?array
    {
        $component = $components->get($code);
        if (! $component || $amount <= 0) return null;
        return ['component_id' => $component->id, 'type' => $type, 'quantity' => $quantity, 'unit_rate' => $rate, 'basis_amount' => null, 'amount' => ceil($amount), 'formula_note' => $note, 'source_type' => 'payroll_calculator', 'source_id' => null, 'sort_order' => $component->sort_order];
    }
}
