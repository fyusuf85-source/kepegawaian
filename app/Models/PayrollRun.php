<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollRun extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['calculated_at'=>'datetime','total_gross'=>'decimal:2','total_deduction'=>'decimal:2','total_net'=>'decimal:2','total_employer_contribution'=>'decimal:2']; } public function period(): BelongsTo { return $this->belongsTo(PayrollPeriod::class,'payroll_period_id'); } public function employees(): HasMany { return $this->hasMany(PayrollRunEmployee::class); } }
