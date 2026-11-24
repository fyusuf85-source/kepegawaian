<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeBankAccountController extends Controller
{
    use EnsuresEmployeeAccess;

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), ['employee.sensitive.view']);
        $this->ensurePermission($request->user(), ['employee.update', 'employee.payroll_profile.manage']);
        $this->ensureVisible($request->user(), $employee);

        $account = $employee->bankAccounts()->create($this->validated($request));

        if ($account->is_primary) {
            $employee->bankAccounts()->whereKeyNot($account->id)->update(['is_primary' => false]);
        }

        return back()->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeBankAccount $account): RedirectResponse
    {
        $this->ensurePermission($request->user(), ['employee.sensitive.view']);
        $this->ensurePermission($request->user(), ['employee.update', 'employee.payroll_profile.manage']);
        $this->ensureVisible($request->user(), $employee);
        abort_unless($account->employee_id === $employee->id, 404);

        $account->delete();

        return back()->with('success', 'Rekening bank berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:150'],
        ]) + ['is_primary' => $request->boolean('is_primary')];
    }
}
