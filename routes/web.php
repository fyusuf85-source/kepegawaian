<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecreeController;
use App\Http\Controllers\EmployeeAssignmentController;
use App\Http\Controllers\EmployeeBankAccountController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeEducationController;
use App\Http\Controllers\EmployeeFamilyController;
use App\Http\Controllers\EmployeePayrollProfileController;
use App\Http\Controllers\EmployeeStatusHistoryController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\ExecutiveReportController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollComponentController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PayrollAdjustmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleAssignmentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacancyController;
use App\Http\Controllers\WorkScheduleController;
use App\Support\MasterData;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('ubah-kata-sandi', [PasswordController::class, 'edit'])->name('password.change');
    Route::post('ubah-kata-sandi', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('profil', [ProfileController::class, 'show'])->name('profile.show');

        Route::get('pegawai/impor', [EmployeeController::class, 'importForm'])->name('employees.import.form');
        Route::post('pegawai/impor', [EmployeeController::class, 'import'])->name('employees.import');
        Route::get('pegawai/impor/template', [EmployeeController::class, 'downloadTemplate'])->name('employees.import.template');
        Route::get('pegawai/ekspor', [EmployeeController::class, 'export'])->name('employees.export');

        Route::get('pegawai', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('pegawai/tambah', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('pegawai', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('pegawai/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('pegawai/{employee}/ubah', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('pegawai/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('pegawai/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        Route::get('pegawai/{employee}/foto', [EmployeeController::class, 'photo'])->name('employees.photo');

        Route::post('pegawai/{employee}/keluarga', [EmployeeFamilyController::class, 'store'])->name('employees.families.store');
        Route::put('pegawai/{employee}/keluarga/{family}', [EmployeeFamilyController::class, 'update'])->name('employees.families.update');
        Route::delete('pegawai/{employee}/keluarga/{family}', [EmployeeFamilyController::class, 'destroy'])->name('employees.families.destroy');

        Route::post('pegawai/{employee}/pendidikan', [EmployeeEducationController::class, 'store'])->name('employees.educations.store');
        Route::delete('pegawai/{employee}/pendidikan/{education}', [EmployeeEducationController::class, 'destroy'])->name('employees.educations.destroy');

        Route::post('pegawai/{employee}/rekening', [EmployeeBankAccountController::class, 'store'])->name('employees.bank-accounts.store');
        Route::delete('pegawai/{employee}/rekening/{account}', [EmployeeBankAccountController::class, 'destroy'])->name('employees.bank-accounts.destroy');

        Route::post('pegawai/{employee}/dokumen', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
        Route::get('pegawai/{employee}/dokumen/{document}/unduh', [EmployeeDocumentController::class, 'download'])->name('employees.documents.download');
        Route::post('pegawai/{employee}/dokumen/{document}/verifikasi', [EmployeeDocumentController::class, 'verify'])->name('employees.documents.verify');
        Route::delete('pegawai/{employee}/dokumen/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employees.documents.destroy');

        Route::post('pegawai/{employee}/riwayat-status', [EmployeeStatusHistoryController::class, 'store'])->name('employees.status-histories.store');
        Route::post('pegawai/{employee}/penempatan', [EmployeeAssignmentController::class, 'store'])->name('employees.assignments.store');
        Route::put('pegawai/{employee}/profil-gaji', [EmployeePayrollProfileController::class, 'update'])->name('employees.payroll-profile.update');

        Route::get('rekrutmen/lowongan', [VacancyController::class, 'index'])->name('vacancies.index');
        Route::get('rekrutmen/lowongan/tambah', [VacancyController::class, 'create'])->name('vacancies.create');
        Route::post('rekrutmen/lowongan', [VacancyController::class, 'store'])->name('vacancies.store');
        Route::get('rekrutmen/lowongan/{vacancy}', [VacancyController::class, 'show'])->name('vacancies.show');
        Route::get('rekrutmen/lowongan/{vacancy}/ubah', [VacancyController::class, 'edit'])->name('vacancies.edit');
        Route::put('rekrutmen/lowongan/{vacancy}', [VacancyController::class, 'update'])->name('vacancies.update');
        Route::delete('rekrutmen/lowongan/{vacancy}', [VacancyController::class, 'destroy'])->name('vacancies.destroy');
        Route::post('rekrutmen/lowongan/{vacancy}/status', [VacancyController::class, 'updateStatus'])->name('vacancies.status');

        Route::get('rekrutmen/pelamar', [ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('rekrutmen/pelamar/tambah', [ApplicantController::class, 'create'])->name('applicants.create');
        Route::post('rekrutmen/pelamar', [ApplicantController::class, 'store'])->name('applicants.store');
        Route::get('rekrutmen/pelamar/{applicant}', [ApplicantController::class, 'show'])->name('applicants.show');
        Route::get('rekrutmen/pelamar/{applicant}/ubah', [ApplicantController::class, 'edit'])->name('applicants.edit');
        Route::put('rekrutmen/pelamar/{applicant}', [ApplicantController::class, 'update'])->name('applicants.update');
        Route::delete('rekrutmen/pelamar/{applicant}', [ApplicantController::class, 'destroy'])->name('applicants.destroy');
        Route::post('rekrutmen/pelamar/{applicant}/dokumen', [ApplicantController::class, 'storeDocument'])->name('applicants.documents.store');
        Route::get('rekrutmen/pelamar/{applicant}/dokumen/{document}/unduh', [ApplicantController::class, 'downloadDocument'])->name('applicants.documents.download');
        Route::delete('rekrutmen/pelamar/{applicant}/dokumen/{document}', [ApplicantController::class, 'destroyDocument'])->name('applicants.documents.destroy');
        Route::post('rekrutmen/pelamar/{applicant}/status', [ApplicantController::class, 'transition'])->name('applicants.status');
        Route::post('rekrutmen/pelamar/{applicant}/wawancara', [ApplicantController::class, 'storeInterview'])->name('applicants.interviews.store');
        Route::put('rekrutmen/pelamar/{applicant}/wawancara/{interview}', [ApplicantController::class, 'updateInterview'])->name('applicants.interviews.update');
        Route::post('rekrutmen/pelamar/{applicant}/penilaian', [ApplicantController::class, 'storeScores'])->name('applicants.scores.store');
        Route::post('rekrutmen/pelamar/{applicant}/keputusan', [ApplicantController::class, 'decide'])->name('applicants.decide');
        Route::get('rekrutmen/pelamar/{applicant}/konversi', [ApplicantController::class, 'convertForm'])->name('applicants.convert.form');
        Route::post('rekrutmen/pelamar/{applicant}/konversi', [ApplicantController::class, 'convert'])->name('applicants.convert');

        Route::get('kontrak', [ContractController::class, 'index'])->name('contracts.index');
        Route::get('kontrak/tambah', [ContractController::class, 'create'])->name('contracts.create');
        Route::post('kontrak', [ContractController::class, 'store'])->name('contracts.store');
        Route::get('kontrak/{contract}', [ContractController::class, 'show'])->name('contracts.show');
        Route::get('kontrak/{contract}/ubah', [ContractController::class, 'edit'])->name('contracts.edit');
        Route::put('kontrak/{contract}', [ContractController::class, 'update'])->name('contracts.update');
        Route::delete('kontrak/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
        Route::post('kontrak/{contract}/ajukan', [ContractController::class, 'submit'])->name('contracts.submit');
        Route::get('kontrak/{contract}/perpanjang', [ContractController::class, 'renewForm'])->name('contracts.renew.form');
        Route::post('kontrak/{contract}/perpanjang', [ContractController::class, 'renew'])->name('contracts.renew');
        Route::get('kontrak/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
        Route::get('kontrak/{contract}/unduh', [ContractController::class, 'download'])->name('contracts.download');

        Route::get('sk', [DecreeController::class, 'index'])->name('decrees.index');
        Route::get('sk/tambah', [DecreeController::class, 'create'])->name('decrees.create');
        Route::post('sk', [DecreeController::class, 'store'])->name('decrees.store');
        Route::get('sk/{decree}', [DecreeController::class, 'show'])->name('decrees.show');
        Route::get('sk/{decree}/ubah', [DecreeController::class, 'edit'])->name('decrees.edit');
        Route::put('sk/{decree}', [DecreeController::class, 'update'])->name('decrees.update');
        Route::delete('sk/{decree}', [DecreeController::class, 'destroy'])->name('decrees.destroy');
        Route::post('sk/{decree}/ajukan', [DecreeController::class, 'submit'])->name('decrees.submit');
        Route::get('sk/{decree}/pdf', [DecreeController::class, 'pdf'])->name('decrees.pdf');
        Route::get('sk/{decree}/unduh', [DecreeController::class, 'download'])->name('decrees.download');

        Route::get('mutasi', [MutationController::class, 'index'])->name('mutations.index');
        Route::get('mutasi/tambah', [MutationController::class, 'create'])->name('mutations.create');
        Route::post('mutasi', [MutationController::class, 'store'])->name('mutations.store');
        Route::get('mutasi/{mutation}', [MutationController::class, 'show'])->name('mutations.show');
        Route::post('mutasi/{mutation}/ajukan', [MutationController::class, 'submit'])->name('mutations.submit');
        Route::post('mutasi/{mutation}/terapkan', [MutationController::class, 'apply'])->name('mutations.apply');
        Route::post('mutasi/{mutation}/sk', [MutationController::class, 'createDecree'])->name('mutations.decree');

        Route::get('surat', [LetterController::class, 'index'])->name('letters.index');
        Route::get('surat/tambah', [LetterController::class, 'create'])->name('letters.create');
        Route::post('surat', [LetterController::class, 'store'])->name('letters.store');
        Route::get('surat/{letter}', [LetterController::class, 'show'])->name('letters.show');
        Route::get('surat/{letter}/ubah', [LetterController::class, 'edit'])->name('letters.edit');
        Route::put('surat/{letter}', [LetterController::class, 'update'])->name('letters.update');
        Route::delete('surat/{letter}', [LetterController::class, 'destroy'])->name('letters.destroy');
        Route::post('surat/{letter}/ajukan', [LetterController::class, 'submit'])->name('letters.submit');
        Route::post('surat/{letter}/kirim', [LetterController::class, 'send'])->name('letters.send');
        Route::get('surat/{letter}/pdf', [LetterController::class, 'pdf'])->name('letters.pdf');
        Route::get('surat/{letter}/unduh', [LetterController::class, 'download'])->name('letters.download');

        Route::get('presensi', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('presensi/masuk', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
        Route::post('presensi/pulang', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
        Route::get('presensi/riwayat', [AttendanceController::class, 'history'])->name('attendance.history');
        Route::get('presensi/rekap', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('presensi/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('presensi/{attendance}/foto/{type}', [AttendanceController::class, 'photo'])->name('attendance.photo');

        Route::get('koreksi-absensi', [CorrectionController::class, 'index'])->name('corrections.index');
        Route::get('koreksi-absensi/tambah', [CorrectionController::class, 'create'])->name('corrections.create');
        Route::post('koreksi-absensi', [CorrectionController::class, 'store'])->name('corrections.store');
        Route::get('koreksi-absensi/{correction}', [CorrectionController::class, 'show'])->name('corrections.show');
        Route::post('koreksi-absensi/{correction}/setuju-atasan', [CorrectionController::class, 'approveSupervisor'])->name('corrections.approve-supervisor');
        Route::post('koreksi-absensi/{correction}/setuju-hrd', [CorrectionController::class, 'approveHrd'])->name('corrections.approve-hrd');
        Route::post('koreksi-absensi/{correction}/tolak', [CorrectionController::class, 'reject'])->name('corrections.reject');
        Route::post('koreksi-absensi/{correction}/batal', [CorrectionController::class, 'cancel'])->name('corrections.cancel');
        Route::get('koreksi-absensi/{correction}/bukti', [CorrectionController::class, 'evidence'])->name('corrections.evidence');

        Route::get('dispensasi-absensi', [ExceptionController::class, 'index'])->name('exceptions.index');
        Route::get('dispensasi-absensi/tambah', [ExceptionController::class, 'create'])->name('exceptions.create');
        Route::post('dispensasi-absensi', [ExceptionController::class, 'store'])->name('exceptions.store');
        Route::post('dispensasi-absensi/{exception}/setuju', [ExceptionController::class, 'approve'])->name('exceptions.approve');
        Route::post('dispensasi-absensi/{exception}/tolak', [ExceptionController::class, 'reject'])->name('exceptions.reject');
        Route::get('dispensasi-absensi/{exception}/bukti', [ExceptionController::class, 'evidence'])->name('exceptions.evidence');

        Route::get('izin-cuti', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('izin-cuti/tambah', [LeaveController::class, 'create'])->name('leave.create');
        Route::post('izin-cuti', [LeaveController::class, 'store'])->name('leave.store');
        Route::get('izin-cuti/{leave}', [LeaveController::class, 'show'])->name('leave.show');
        Route::post('izin-cuti/{leave}/setuju-atasan', [LeaveController::class, 'approveSupervisor'])->name('leave.approve-supervisor');
        Route::post('izin-cuti/{leave}/setuju-hrd', [LeaveController::class, 'approveHrd'])->name('leave.approve-hrd');
        Route::post('izin-cuti/{leave}/tolak', [LeaveController::class, 'reject'])->name('leave.reject');
        Route::post('izin-cuti/{leave}/batal', [LeaveController::class, 'cancel'])->name('leave.cancel');
        Route::get('izin-cuti/{leave}/dokumen', [LeaveController::class, 'document'])->name('leave.document');
        Route::get('saldo-cuti', [LeaveBalanceController::class, 'index'])->name('leave.balances.index');
        Route::post('saldo-cuti', [LeaveBalanceController::class, 'store'])->name('leave.balances.store');

        Route::get('keuangan-pribadi', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('keuangan-pribadi/reimbursement', [FinanceController::class, 'storeReimbursement'])->name('finance.reimbursement.store');
        Route::post('keuangan-pribadi/reimbursement/{reimbursement}/approve', [FinanceController::class, 'approveReimbursement'])->name('finance.reimbursement.approve');
        Route::post('keuangan-pribadi/loan', [FinanceController::class, 'storeLoan'])->name('finance.loan.store');
        Route::post('keuangan-pribadi/loan/{loan}/approve', [FinanceController::class, 'approveLoan'])->name('finance.loan.approve');
        Route::post('keuangan-pribadi/{type}/{id}/reject', [FinanceController::class, 'reject'])->name('finance.reject');

        Route::get('persetujuan', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('persetujuan/{approval}', [ApprovalController::class, 'show'])->name('approvals.show');
        Route::post('persetujuan/{approval}/setuju', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('persetujuan/{approval}/tolak', [ApprovalController::class, 'reject'])->name('approvals.reject');
        Route::post('persetujuan/{approval}/batal', [ApprovalController::class, 'cancel'])->name('approvals.cancel');

        Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        foreach (array_keys(MasterData::definitions()) as $resource) {
            Route::get("master/{$resource}", [MasterDataController::class, 'index'])
                ->defaults('resource', $resource)
                ->name("master.{$resource}.index");
            Route::get("master/{$resource}/tambah", [MasterDataController::class, 'create'])
                ->defaults('resource', $resource)
                ->name("master.{$resource}.create");
            Route::post("master/{$resource}", [MasterDataController::class, 'store'])
                ->defaults('resource', $resource)
                ->name("master.{$resource}.store");
            Route::get("master/{$resource}/{id}/ubah", [MasterDataController::class, 'edit'])
                ->defaults('resource', $resource)
                ->name("master.{$resource}.edit");
            Route::put("master/{$resource}/{id}", [MasterDataController::class, 'update'])
                ->defaults('resource', $resource)
                ->name("master.{$resource}.update");
            Route::delete("master/{$resource}/{id}", [MasterDataController::class, 'destroy'])
                ->defaults('resource', $resource)
                ->name("master.{$resource}.destroy");
        }

        Route::get('jadwal-kerja', [WorkScheduleController::class, 'index'])->name('schedules.index');
        Route::get('jadwal-kerja/tambah', [WorkScheduleController::class, 'create'])->name('schedules.create');
        Route::post('jadwal-kerja', [WorkScheduleController::class, 'store'])->name('schedules.store');
        Route::get('jadwal-kerja/{schedule}/ubah', [WorkScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('jadwal-kerja/{schedule}', [WorkScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('jadwal-kerja/{schedule}', [WorkScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('penugasan-jadwal', [ScheduleAssignmentController::class, 'index'])->name('schedule-assignments.index');
        Route::post('penugasan-jadwal', [ScheduleAssignmentController::class, 'store'])->name('schedule-assignments.store');
        Route::delete('penugasan-jadwal/{assignment}', [ScheduleAssignmentController::class, 'destroy'])->name('schedule-assignments.destroy');

        Route::get('pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('pengguna/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('pengguna', [UserController::class, 'store'])->name('users.store');
        Route::get('pengguna/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
        Route::put('pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('pengguna/{user}/reset-kata-sandi', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('pengguna/{user}/status', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('peran', [RoleController::class, 'index'])->name('roles.index');
        Route::get('peran/{role}/hak-akses', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('peran/{role}/hak-akses', [RoleController::class, 'update'])->name('roles.update');

        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('riwayat-login', [LoginHistoryController::class, 'index'])->name('login-histories.index');

        Route::get('pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::put('pengaturan', [SettingController::class, 'update'])->name('settings.update');

        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('payroll/tambah', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('payroll/{period}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{period}/snapshot', [PayrollController::class, 'generateSnapshot'])->name('payroll.snapshot');
        Route::post('payroll/{period}/hitung', [PayrollController::class, 'calculate'])->name('payroll.calculate');
        Route::post('payroll/{period}/ajukan', [PayrollController::class, 'submitApproval'])->name('payroll.submit');
        Route::post('payroll/{period}/kunci', [PayrollController::class, 'lock'])->name('payroll.lock');
        Route::post('payroll/{period}/slip/generate', [PayslipController::class, 'generate'])->name('payroll.payslips.generate');
        Route::get('payroll/{period}/slip', [PayslipController::class, 'batchPdf'])->name('payroll.payslips.batch');
        Route::get('payroll/{period}/ekspor', [PayrollController::class, 'export'])->name('payroll.export');

        Route::get('payroll-penyesuaian', [PayrollAdjustmentController::class, 'index'])->name('payroll.adjustments.index');
        Route::get('payroll-penyesuaian/tambah', [PayrollAdjustmentController::class, 'create'])->name('payroll.adjustments.create');
        Route::post('payroll-penyesuaian', [PayrollAdjustmentController::class, 'store'])->name('payroll.adjustments.store');
        Route::post('payroll-penyesuaian/{adjustment}/setuju', [PayrollAdjustmentController::class, 'approve'])->name('payroll.adjustments.approve');
        Route::delete('payroll-penyesuaian/{adjustment}', [PayrollAdjustmentController::class, 'destroy'])->name('payroll.adjustments.destroy');

        Route::get('kpi', [KpiController::class, 'index'])->name('kpi.index');
        Route::post('kpi/periode', [KpiController::class, 'storePeriod'])->name('kpi.periods.store');
        Route::post('kpi/periode/{period}/status', [KpiController::class, 'updatePeriodStatus'])->name('kpi.periods.status');
        Route::post('kpi/template', [KpiController::class, 'storeTemplate'])->name('kpi.templates.store');
        Route::get('kpi/penilaian/tambah', [KpiController::class, 'createAssessment'])->name('kpi.assessments.create');
        Route::post('kpi/penilaian', [KpiController::class, 'storeAssessment'])->name('kpi.assessments.store');
        Route::get('kpi/penilaian/{assessment}', [KpiController::class, 'showAssessment'])->name('kpi.assessments.show');
        Route::get('kpi/penilaian/{assessment}/ubah', [KpiController::class, 'editAssessment'])->name('kpi.assessments.edit');
        Route::put('kpi/penilaian/{assessment}', [KpiController::class, 'updateAssessment'])->name('kpi.assessments.update');
        Route::post('kpi/penilaian/{assessment}/ajukan', [KpiController::class, 'submitAssessment'])->name('kpi.assessments.submit');
        Route::post('kpi/penilaian/{assessment}/verifikasi', [KpiController::class, 'verifyAssessment'])->name('kpi.assessments.verify');
        Route::post('kpi/penilaian/{assessment}/setujui', [KpiController::class, 'approveAssessment'])->name('kpi.assessments.approve');
        Route::post('kpi/penilaian/{assessment}/buka-kunci', [KpiController::class, 'unlockAssessment'])->name('kpi.assessments.unlock');

        Route::get('pembinaan', [KpiController::class, 'coachingIndex'])->name('coaching.index');
        Route::post('pembinaan', [KpiController::class, 'storeCoaching'])->name('coaching.store');
        Route::post('pembinaan/{record}/setujui', [KpiController::class, 'approveCoaching'])->name('coaching.approve');

        Route::get('laporan/eksekutif', [ExecutiveReportController::class, 'index'])->name('reports.executive');
        Route::get('laporan/absensi', [ExecutiveReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('laporan/payroll', [ExecutiveReportController::class, 'payroll'])->name('reports.payroll');
        Route::get('laporan/absensi/ekspor', [ExecutiveReportController::class, 'exportAttendance'])->name('reports.attendance.export');
        Route::get('laporan/payroll/ekspor', [ExecutiveReportController::class, 'exportPayroll'])->name('reports.payroll.export');

        Route::get('slip-gaji', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('slip-gaji/{payslip}/unduh', [PayslipController::class, 'download'])->name('payslips.download');

        Route::get('master/komponen-gaji', [PayrollComponentController::class, 'index'])->name('payroll.components.index');
        Route::get('master/komponen-gaji/tambah', [PayrollComponentController::class, 'create'])->name('payroll.components.create');
        Route::post('master/komponen-gaji', [PayrollComponentController::class, 'store'])->name('payroll.components.store');
        Route::get('master/komponen-gaji/{component}/ubah', [PayrollComponentController::class, 'edit'])->name('payroll.components.edit');
        Route::put('master/komponen-gaji/{component}', [PayrollComponentController::class, 'update'])->name('payroll.components.update');
    });
});
