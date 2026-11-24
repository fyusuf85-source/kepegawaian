<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollRunEmployee extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['gross'=>'decimal:2','total_deduction'=>'decimal:2','employer_contribution'=>'decimal:2','net'=>'decimal:2']; } public function run(): BelongsTo { return $this->belongsTo(PayrollRun::class,'payroll_run_id'); } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function attendanceSummary(): BelongsTo { return $this->belongsTo(AttendancePeriodSummary::class, 'attendance_summary_id'); } public function details(): HasMany { return $this->hasMany(PayrollDetail::class); } }
