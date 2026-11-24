<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayrollPdfService
{
    public function generateBatch(PayrollPeriod $period, PayrollRun $run): string
    {
        $run->load(['employees.employee.orgUnit', 'employees.employee.position', 'employees.details.component', 'period']);
        $html = view('payroll.pdf.batch', ['period' => $period, 'run' => $run, 'rows' => $run->employees])->render();
        $output = Pdf::loadHTML($html)->setPaper('a4')->output();
        $path = 'payslips/'.$period->code.'/slip-gaji-'.$period->code.'-v'.$run->version.'.pdf';
        Storage::disk('local')->put($path, $output);
        return $path;
    }

    public function generateOne(PayrollRunEmployee $row): string
    {
        $row->loadMissing(['employee.orgUnit', 'employee.position', 'details.component', 'run.period']);
        $period = $row->run->period;
        $html = view('payroll.pdf.slip', ['period' => $period, 'run' => $row->run, 'row' => $row])->render();
        $output = Pdf::loadHTML($html)->setPaper('a4')->output();
        $name = Str::slug(($row->employee->nip ?? 'pegawai').'-'.$period->code);
        $path = 'payslips/'.$period->code.'/'.$name.'.pdf';
        Storage::disk('local')->put($path, $output);
        return $path;
    }
}
