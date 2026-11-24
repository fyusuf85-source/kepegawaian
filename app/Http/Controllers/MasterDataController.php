<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Support\MasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(string $resource): View
    {
        $def = MasterData::resolve($resource);
        $this->authorizeView($def);

        $query = $def['model']::query()->with($def['with'] ?? []);

        if ($search = trim((string) request('q'))) {
            $query->where(function ($q) use ($def, $search) {
                foreach ($def['searchable'] as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        foreach ($def['order_by'] as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return view('master.index', [
            'def' => $def,
            'resource' => $resource,
            'rows' => $query->paginate(15)->withQueryString(),
            'canManage' => request()->user()->hasPermission($def['permission']),
        ]);
    }

    public function create(string $resource): View
    {
        $def = MasterData::resolve($resource);
        $this->authorizeManage($def);

        return view('master.form', [
            'def' => $def,
            'resource' => $resource,
            'record' => null,
            'options' => MasterData::selectOptions($def),
        ]);
    }

    public function store(string $resource, Request $request): RedirectResponse
    {
        $def = MasterData::resolve($resource);
        $this->authorizeManage($def);

        $data = $this->validated($def, $request, null);
        $record = $def['model']::create($data);
        $this->afterSave($def, $record);

        return redirect()
            ->route($def['route'].'.index')
            ->with('success', $def['singular'].' berhasil ditambahkan.');
    }

    public function edit(string $resource, int $id): View
    {
        $def = MasterData::resolve($resource);
        $this->authorizeManage($def);

        $record = $def['model']::findOrFail($id);

        return view('master.form', [
            'def' => $def,
            'resource' => $resource,
            'record' => $record,
            'options' => MasterData::selectOptions($def, $record),
        ]);
    }

    public function update(string $resource, int $id, Request $request): RedirectResponse
    {
        $def = MasterData::resolve($resource);
        $this->authorizeManage($def);

        $record = $def['model']::findOrFail($id);
        $data = $this->validated($def, $request, $record);
        $record->update($data);
        $this->afterSave($def, $record);

        return redirect()
            ->route($def['route'].'.index')
            ->with('success', $def['singular'].' berhasil diperbarui.');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $def = MasterData::resolve($resource);
        $this->authorizeManage($def);

        $record = $def['model']::findOrFail($id);

        if (isset($def['delete_guard']) && ($message = $def['delete_guard']($record))) {
            return back()->withErrors(['delete' => $message]);
        }

        try {
            $record->delete();
        } catch (\Illuminate\Database\QueryException) {
            return back()->withErrors(['delete' => 'Data tidak dapat dihapus karena masih dipakai data lain.']);
        }

        return redirect()
            ->route($def['route'].'.index')
            ->with('success', $def['singular'].' berhasil dihapus.');
    }

    protected function authorizeView(array $def): void
    {
        $user = request()->user();
        abort_unless(
            $user->hasPermission($def['view_permission']) || $user->hasPermission($def['permission']),
            403,
            'Anda tidak memiliki hak akses untuk melihat data ini.'
        );
    }

    protected function authorizeManage(array $def): void
    {
        abort_unless(
            request()->user()->hasPermission($def['permission']),
            403,
            'Anda tidak memiliki hak akses untuk mengubah data ini.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(array $def, Request $request, ?Model $record): array
    {
        $table = (new $def['model'])->getTable();
        $rules = [];
        $booleanFields = [];

        foreach ($def['fields'] as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $fieldRules = [];

            if (! empty($field['required'])) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($type === 'checkbox') {
                $booleanFields[] = $name;
                continue;
            }

            $fieldRules[] = match ($type) {
                'number' => 'integer',
                'decimal' => 'numeric',
                'date' => 'date',
                'time' => 'date_format:H:i',
                'employee' => 'integer',
                default => 'string',
            };

            if ($type === 'text' || $type === 'textarea') {
                $fieldRules[] = 'max:65000';
            }

            if ($type === 'select') {
                $fieldRules[] = Rule::in(array_keys($field['options']));
            }

            if ($type === 'org_unit' || $type === 'parent_org_unit') {
                $fieldRules[] = 'exists:org_units,id';
            }

            if ($type === 'employee') {
                $fieldRules[] = 'exists:employees,id';
            }

            if (! empty($field['unique'])) {
                $unique = Rule::unique($table, $name);
                if ($record) {
                    $unique->ignore($record->getKey());
                }
                $fieldRules[] = $unique;
            }

            $rules[$name] = $fieldRules;
        }

        $data = $request->validate($rules);

        foreach ($booleanFields as $field) {
            $data[$field] = $request->boolean($field);
        }

        if (isset($data['parent_id']) && $record instanceof OrgUnit && (int) $data['parent_id'] === $record->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Unit tidak boleh menjadi induk dirinya sendiri.',
            ]);
        }

        return $data;
    }

    protected function afterSave(array $def, Model $record): void
    {
        if ($record instanceof OrgUnit) {
            $this->recalculateOrgLevels($record);
        }
    }

    protected function recalculateOrgLevels(OrgUnit $unit): void
    {
        $level = 1;
        $parent = $unit->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        if ($unit->level !== $level) {
            $unit->forceFill(['level' => $level])->saveQuietly();
        }

        foreach ($unit->children as $child) {
            $this->recalculateOrgLevels($child);
        }
    }
}
