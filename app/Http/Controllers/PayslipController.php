<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsHrAccess;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\PayrollPdfService;
use App\Services\PayslipGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipController extends Controller
{
    use GuardsHrAccess;

    public function __construct(private readonly PayrollPdfService $pdf, private readonly PayslipGenerationService $generation) {}

    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'payroll.view.self');
        return view('payslips.index', [
            'payslips' => Payslip::query()->with('period')->where('employee_id', $request->user()->employee_id ?? 0)->latest()->paginate(15),
        ]);
    }

    public function download(Request $request, Payslip $payslip): StreamedResponse
    {
        $this->ensurePermission($request, 'payslip.download.self');
        abort_unless($payslip->employee_id === $request->user()->employee_id, 403);
        abort_unless($payslip->file_path && Storage::disk('local')->exists($payslip->file_path), 404, 'Berkas slip tidak ditemukan.');
        return Storage::disk('local')->download($payslip->file_path, 'slip-gaji-'.$payslip->period->code.'.pdf');
    }

    public function batchPdf(Request $request, PayrollPeriod $period): StreamedResponse
    {
        $this->ensurePermission($request, 'payroll.report');
        $run = $period->runs()->where('status', 'final')->latest('version')->first();
        if (! $run) throw ValidationException::withMessages(['period' => 'Run payroll final belum tersedia.']);
        $path = $this->pdf->generateBatch($period, $run);
        return Storage::disk('local')->download($path, 'slip-gaji-'.$period->code.'.pdf');
    }

    public function generate(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $this->ensurePermission($request, 'payroll.lock');
        $count = $this->generation->generate($period);
        return back()->with('success', $count.' slip gaji PDF berhasil dibuat.');
    }
}
