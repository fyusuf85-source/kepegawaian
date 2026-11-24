<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresEmployeeAccess;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    use EnsuresEmployeeAccess;

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.document.manage');
        $this->ensureVisible($request->user(), $employee);

        $data = $request->validate([
            'doc_type' => ['required', Rule::in([
                'ktp', 'kk', 'ijazah', 'sertifikat', 'kontrak', 'sk', 'npwp', 'bpjs', 'lainnya',
            ])],
            'number' => ['nullable', 'string', 'max:100'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'is_confidential' => ['nullable', 'boolean'],
        ], [
            'file.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran dokumen maksimal 10 MB.',
        ]);

        $data['file_path'] = $request->file('file')->store('employees/documents', 'local');
        $data['is_confidential'] = $request->boolean('is_confidential');
        $data['uploaded_by'] = $request->user()->id;
        unset($data['file']);

        $employee->documents()->create($data);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function download(Request $request, Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        $this->ensurePermission($request->user(), 'employee.document.view');
        $this->ensureVisible($request->user(), $employee);
        abort_unless($document->employee_id === $employee->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'Berkas tidak ditemukan.');

        $this->audit->recordRaw('view_sensitive', $document->getMorphClass(), $document->id, null, [
            'employee_id' => $employee->id,
            'doc_type' => $document->doc_type,
        ]);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->doc_type.'-'.$employee->nip.'.'.pathinfo($document->file_path, PATHINFO_EXTENSION)
        );
    }

    public function verify(Request $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->ensurePermission($request->user(), ['employee.document.manage', 'employee.update']);
        $this->ensureVisible($request->user(), $employee);
        abort_unless($document->employee_id === $employee->id, 404);

        $document->update([
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Dokumen ditandai terverifikasi.');
    }

    public function destroy(Request $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->ensurePermission($request->user(), 'employee.document.manage');
        $this->ensureVisible($request->user(), $employee);
        abort_unless($document->employee_id === $employee->id, 404);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
