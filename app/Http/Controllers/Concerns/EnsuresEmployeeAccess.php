<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\User;

trait EnsuresEmployeeAccess
{
    protected function ensurePermission(User $user, string|array $permissions): void
    {
        $permissions = (array) $permissions;

        abort_unless(
            $user->hasAnyPermission($permissions),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini.'
        );
    }

    protected function ensureVisible(User $user, Employee $employee): void
    {
        if ($user->hasPermission('employee.view.all')) {
            return;
        }

        if ($user->employee_id === $employee->id) {
            return;
        }

        if ($user->hasPermission('employee.view.unit') && $employee->canBeViewedBy($user)) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke data pegawai ini.');
    }
}
