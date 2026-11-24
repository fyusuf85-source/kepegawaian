<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttendancePeriodSummary extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['generated_at'=>'datetime','is_locked'=>'boolean']; } public function period(): BelongsTo { return $this->belongsTo(PayrollPeriod::class,'payroll_period_id'); } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
