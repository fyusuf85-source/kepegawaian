<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Reimbursement;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee;

        $reimbursements = Reimbursement::query()
            ->when(!$user->hasPermission('reimbursement.view.all'), function ($q) use ($employee) {
                $q->where('employee_id', $employee?->id);
            })
            ->latest()->paginate(15);

        $loans = Loan::query()
            ->when(!$user->hasPermission('loan.view.all'), function ($q) use ($employee) {
                $q->where('employee_id', $employee?->id);
            })
            ->latest()->paginate(15);

        return view('finance.index', compact('reimbursements', 'loans'));
    }

    public function storeReimbursement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'amount' => 'required|numeric|min:1000',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = $request->file('attachment')?->store('finance/reimbursement');
        
        $this->finance->submitReimbursement($request->user()->employee, $data, $path, $request->user());

        return redirect()->route('finance.index')->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function approveReimbursement(Request $request, Reimbursement $reimbursement): RedirectResponse
    {
        if ($request->user()->hasPermission('reimbursement.approve.supervisor')) {
            $this->finance->approveReimbursementBySupervisor($reimbursement, $request->user());
        } elseif ($request->user()->hasPermission('reimbursement.approve.hrd')) {
            $this->finance->approveReimbursementByHrd($reimbursement, $request->user());
        }
        return back()->with('success', 'Berhasil.');
    }

    public function storeLoan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'tenor_months' => 'required|integer|min:1|max:24',
            'reason' => 'required|string',
        ]);

        $this->finance->submitLoan($request->user()->employee, $data, $request->user());

        return redirect()->route('finance.index')->with('success', 'Pengajuan pinjaman berhasil dikirim.');
    }

    public function approveLoan(Request $request, Loan $loan): RedirectResponse
    {
        if ($request->user()->hasPermission('loan.approve.supervisor')) {
            $this->finance->approveLoanBySupervisor($loan, $request->user());
        } elseif ($request->user()->hasPermission('loan.approve.hrd')) {
            $this->finance->approveLoanByHrd($loan, $request->user());
        }
        return back()->with('success', 'Berhasil.');
    }

    public function reject(Request $request, string $type, int $id): RedirectResponse
    {
        $request->validate(['reason' => 'required|string']);
        $actor = $request->user();
        if ($type === 'reimbursement') {
            $this->finance->rejectReimbursement(Reimbursement::findOrFail($id), $actor, $request->reason);
        } else {
            $this->finance->rejectLoan(Loan::findOrFail($id), $actor, $request->reason);
        }
        return back()->with('success', 'Ditolak.');
    }
}
