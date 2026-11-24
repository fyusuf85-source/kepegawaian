<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait GuardsHrAccess
{
    protected function ensurePermission(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()->hasPermission($permission),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini.'
        );
    }

    /**
     * Batasi data berdasarkan cakupan pegawai pengguna.
     */
    protected function scopeEmployeeRecords(Builder $query, User $user, string $column = 'employee_id'): Builder
    {
        if ($user->hasPermission('employee.view.all')) {
            return $query;
        }

        if ($user->hasPermission('employee.view.unit')) {
            return $query->whereIn($column, Employee::query()->visibleTo($user)->select('id'));
        }

        return $query->where($column, $user->employee_id ?? 0);
    }

    protected function ensureRecordVisible(Request $request, int $employeeId): void
    {
        $visible = Employee::query()
            ->visibleTo($request->user())
            ->whereKey($employeeId)
            ->exists();

        abort_unless($visible, 403, 'Anda tidak memiliki akses ke data pegawai ini.');
    }
}
