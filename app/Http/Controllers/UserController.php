<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensure($request, 'user.view');

        $users = User::query()
            ->with(['employee:id,full_name,nip', 'roles:id,code,name'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->input('q'));
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'canManage' => $request->user()->hasPermission('user.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensure($request, 'user.manage');

        return view('users.form', [
            'user' => new User(['is_active' => true, 'must_change_password' => true]),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensure($request, 'user.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash', 'max:60', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'employee_id' => ['nullable', 'exists:employees,id', 'unique:users,employee_id'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'org_unit_id' => ['nullable', 'exists:org_units,id'],
        ], [
            'role_ids.required' => 'Pilih minimal satu peran.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
            'must_change_password' => $request->boolean('must_change_password'),
        ]);

        $user->roles()->sync(
            collect($data['role_ids'])->mapWithKeys(fn ($id) => [
                $id => ['org_unit_id' => $data['org_unit_id'] ?? null],
            ])->all()
        );

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensure($request, 'user.manage');
        $user->load('roles');

        return view('users.form', [
            'user' => $user,
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensure($request, 'user.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash', 'max:60', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'employee_id' => ['nullable', 'exists:employees,id', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'org_unit_id' => ['nullable', 'exists:org_units,id'],
        ]);

        $attributes = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'must_change_password' => $request->boolean('must_change_password'),
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user->update($attributes);

        $user->roles()->sync(
            collect($data['role_ids'])->mapWithKeys(fn ($id) => [
                $id => ['org_unit_id' => $data['org_unit_id'] ?? null],
            ])->all()
        );

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $this->ensure($request, 'user.reset_password');

        abort_if($user->id === $request->user()->id, 422, 'Gunakan menu ubah kata sandi untuk akun sendiri.');

        $temporary = Str::password(12);

        $user->forceFill([
            'password' => Hash::make($temporary),
            'must_change_password' => true,
        ])->save();

        $audit->recordRaw('update', $user->getMorphClass(), $user->id, null, [
            'password' => '[direset administrator]',
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "Kata sandi sementara untuk {$user->username}: {$temporary} (tampilkan sekali, minta pengguna segera mengganti).");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->ensure($request, 'user.deactivate');

        abort_if($user->id === $request->user()->id, 422, 'Anda tidak dapat menonaktifkan akun sendiri.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    protected function ensure(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()->hasPermission($permission),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(),
            'employees' => Employee::query()->orderBy('full_name')->pluck('full_name', 'id'),
            'orgUnits' => OrgUnit::query()->orderBy('level')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
