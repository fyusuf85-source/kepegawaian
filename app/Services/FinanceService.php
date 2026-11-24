<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Loan;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function submitReimbursement(Employee $employee, array $data, ?string $attachmentPath, User $actor): Reimbursement
    {
        return DB::transaction(function () use ($employee, $data, $attachmentPath, $actor) {
            $supervisorId = $employee->assignments()->latest('effective_from')->first()?$employee->assignments()->latest('effective_from')->first()->direct_supervisor_id : null;

            $reimbursement = Reimbursement::create([
                'employee_id' => $employee->id,
                'supervisor_id' => $supervisorId,
                'title' => $data['title'],
                'amount' => $data['amount'],
                'expense_date' => $data['expense_date'],
                'description' => $data['description'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => 'menunggu_atasan',
            ]);

            if ($supervisorId) {
                $supervisorEmp = Employee::find($supervisorId);
                if ($supervisorEmp?->user) {
                    $this->notifications->send(
                        $supervisorEmp->user,
                        'approval',
                        'Persetujuan Reimbursement',
                        'Pengajuan reimbursement baru dari ' . $employee->full_name . ' menunggu persetujuan Anda.'
                    );
                }
            }

            return $reimbursement;
        });
    }

    public function approveReimbursementBySupervisor(Reimbursement $reimbursement, User $actor, ?string $notes = null): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $actor, $notes) {
            $reimbursement = Reimbursement::query()->lockForUpdate()->findOrFail($reimbursement->id);
            if ($reimbursement->status !== 'menunggu_atasan') {
                throw ValidationException::withMessages(['reimbursement' => 'Pengajuan tidak dalam status menunggu atasan.']);
            }

            $reimbursement->update([
                'status' => 'menunggu_hrd',
                'approved_by_supervisor_id' => $actor->employee_id,
                'approved_by_supervisor_at' => now(),
            ]);

            // Notify HRD
            $hrdUsers = User::whereHas('role.permissions', fn($q) => $q->where('slug', 'reimbursement.verify'))->get();
            foreach ($hrdUsers as $hrd) {
                $this->notifications->send(
                    $hrd,
                    'approval',
                    'Verifikasi Reimbursement HRD',
                    'Reimbursement ' . $reimbursement->title . ' telah disetujui atasan dan menunggu verifikasi HRD.'
                );
            }

            return $reimbursement;
        });
    }

    public function approveReimbursementByHrd(Reimbursement $reimbursement, User $actor, ?string $notes = null): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $actor) {
            $reimbursement = Reimbursement::query()->lockForUpdate()->findOrFail($reimbursement->id);
            if ($reimbursement->status !== 'menunggu_hrd') {
                throw ValidationException::withMessages(['reimbursement' => 'Pengajuan tidak dalam status menunggu HRD.']);
            }

            $reimbursement->update([
                'status' => 'disetujui',
                'approved_by_hrd_id' => $actor->id,
                'approved_by_hrd_at' => now(),
            ]);

            if ($reimbursement->employee->user) {
                $this->notifications->send(
                    $reimbursement->employee->user,
                    'info',
                    'Reimbursement Disetujui',
                    'Pengajuan reimbursement ' . $reimbursement->title . ' telah disetujui.'
                );
            }

            return $reimbursement;
        });
    }

    public function rejectReimbursement(Reimbursement $reimbursement, User $actor, string $reason): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $actor, $reason) {
            $reimbursement = Reimbursement::query()->lockForUpdate()->findOrFail($reimbursement->id);
            $reimbursement->update([
                'status' => 'ditolak',
                'rejection_reason' => $reason,
            ]);

            if ($reimbursement->employee->user) {
                $this->notifications->send(
                    $reimbursement->employee->user,
                    'info',
                    'Reimbursement Ditolak',
                    'Pengajuan reimbursement ' . $reimbursement->title . ' ditolak: ' . $reason
                );
            }

            return $reimbursement;
        });
    }

    public function submitLoan(Employee $employee, array $data, User $actor): Loan
    {
        return DB::transaction(function () use ($employee, $data) {
            $supervisorId = $employee->assignments()->latest('effective_from')->first()?$employee->assignments()->latest('effective_from')->first()->direct_supervisor_id : null;
            $tenor = (int) $data['tenor_months'];
            $amount = (float) $data['amount'];
            $monthly = round($amount / max(1, $tenor), 2);

            $loan = Loan::create([
                'employee_id' => $employee->id,
                'supervisor_id' => $supervisorId,
                'amount' => $amount,
                'tenor_months' => $tenor,
                'monthly_installment' => $monthly,
                'reason' => $data['reason'],
                'status' => 'menunggu_atasan',
            ]);

            if ($supervisorId) {
                $supervisorEmp = Employee::find($supervisorId);
                if ($supervisorEmp?->user) {
                    $this->notifications->send(
                        $supervisorEmp->user,
                        'approval',
                        'Persetujuan Pinjaman / Kasbon',
                        'Pengajuan pinjaman baru dari ' . $employee->full_name . ' menunggu persetujuan Anda.'
                    );
                }
            }

            return $loan;
        });
    }

    public function approveLoanBySupervisor(Loan $loan, User $actor): Loan
    {
        return DB::transaction(function () use ($loan, $actor) {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            if ($loan->status !== 'menunggu_atasan') {
                throw ValidationException::withMessages(['loan' => 'Pengajuan pinjaman tidak dalam status menunggu atasan.']);
            }

            $loan->update([
                'status' => 'menunggu_hrd',
                'approved_by_supervisor_id' => $actor->employee_id,
                'approved_by_supervisor_at' => now(),
            ]);

            $hrdUsers = User::whereHas('role.permissions', fn($q) => $q->where('slug', 'loan.verify'))->get();
            foreach ($hrdUsers as $hrd) {
                $this->notifications->send(
                    $hrd,
                    'approval',
                    'Verifikasi Pinjaman HRD',
                    'Pengajuan pinjaman sebesar Rp ' . number_format($loan->amount, 0, ',', '.') . ' telah disetujui atasan.'
                );
            }

            return $loan;
        });
    }

    public function approveLoanByHrd(Loan $loan, User $actor): Loan
    {
        return DB::transaction(function () use ($loan, $actor) {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            if ($loan->status !== 'menunggu_hrd') {
                throw ValidationException::withMessages(['loan' => 'Pengajuan pinjaman tidak dalam status menunggu HRD.']);
            }

            $loan->update([
                'status' => 'disetujui',
                'approved_by_hrd_id' => $actor->id,
                'approved_by_hrd_at' => now(),
            ]);

            if ($loan->employee->user) {
                $this->notifications->send(
                    $loan->employee->user,
                    'info',
                    'Pinjaman Disetujui',
                    'Pengajuan pinjaman sebesar Rp ' . number_format($loan->amount, 0, ',', '.') . ' telah disetujui.'
                );
            }

            return $loan;
        });
    }

    public function rejectLoan(Loan $loan, User $actor, string $reason): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $reason) {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);
            $loan->update([
                'status' => 'ditolak',
                'rejection_reason' => $reason,
            ]);

            if ($loan->employee->user) {
                $this->notifications->send(
                    $loan->employee->user,
                    'info',
                    'Pinjaman Ditolak',
                    'Pengajuan pinjaman ditolak: ' . $reason
                );
            }

            return $loan;
        });
    }
}
