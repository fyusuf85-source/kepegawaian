<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('role.manage'), 403);

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function edit(Request $request, Role $role): View
    {
        abort_unless($request->user()->hasPermission('role.manage'), 403);

        $role->load('permissions:id');
        $permissions = Permission::query()->orderBy('module')->orderBy('code')->get()->groupBy('module');

        return view('roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'assigned' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('role.manage'), 403);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $before = $role->permissions()->pluck('permissions.id')->all();
        $role->permissions()->sync($data['permissions'] ?? []);

        $audit->recordRaw('update', $role->getMorphClass(), $role->id, [
            'permissions' => $before,
        ], [
            'permissions' => $data['permissions'] ?? [],
        ]);

        return redirect()->route('roles.index')->with('success', 'Hak akses peran berhasil diperbarui.');
    }
}
