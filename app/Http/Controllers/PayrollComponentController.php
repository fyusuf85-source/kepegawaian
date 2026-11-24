<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollComponentController extends Controller
{
    use GuardsHrAccess;
    public function index(Request $r): View { $this->ensurePermission($r, 'payroll.component.view'); return view('payroll.components.index', ['components' => PayrollComponent::query()->orderBy('sort_order')->paginate(20)]); }
    public function create(Request $r): View { $this->ensurePermission($r, 'payroll.component.manage'); return view('payroll.components.form', ['component' => new PayrollComponent]); }
    public function store(Request $r): RedirectResponse { $this->ensurePermission($r, 'payroll.component.manage'); PayrollComponent::create($this->data($r)); return redirect()->route('payroll.components.index')->with('success', 'Komponen gaji dibuat.'); }
    public function edit(Request $r, PayrollComponent $component): View { $this->ensurePermission($r, 'payroll.component.manage'); return view('payroll.components.form', compact('component')); }
    public function update(Request $r, PayrollComponent $component): RedirectResponse { $this->ensurePermission($r, 'payroll.component.manage'); $component->update($this->data($r)); return redirect()->route('payroll.components.index')->with('success', 'Komponen gaji diperbarui.'); }
    private function data(Request $r): array
    {
        $data = $r->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'name' => 'required|string|max:150',
            'type' => 'required|in:penghasilan,potongan,iuran_pemberi',
            'category' => 'required|string|max:50',
            'calculation_method' => 'required|string|max:30',
            'amount' => 'nullable|numeric',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'formula' => 'nullable|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_taxable' => 'sometimes|boolean', 'is_bpjs_base' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean', 'sort_order' => 'nullable|integer',
        ]);

        if ($data['calculation_method'] === 'percentage' && $data['percentage'] === null) {
            abort(422, 'Persentase wajib diisi untuk metode persentase.');
        }
        if ($data['calculation_method'] === 'fixed' && $data['amount'] === null) {
            abort(422, 'Nominal wajib diisi untuk metode fixed.');
        }
        if (! empty($data['formula'])) {
            $allowed = ['gaji_pokok', 'hari_hadir', 'menit_terlambat', 'jumlah_anak', 'basis_bpjs', 'hari_kerja', 'jarak_km'];
            preg_match_all('/[a-z_][a-z0-9_]*/i', $data['formula'], $matches);
            $unknown = array_diff(array_unique($matches[0]), $allowed);
            if ($unknown || preg_match('/[^a-z0-9_+*\/.()\s-]/i', $data['formula'])) {
                abort(422, 'Formula memuat variabel atau karakter yang tidak diizinkan.');
            }
        }
        return $data + ['is_taxable' => $r->boolean('is_taxable'), 'is_bpjs_base' => $r->boolean('is_bpjs_base'), 'is_active' => $r->boolean('is_active')];
    }
}
