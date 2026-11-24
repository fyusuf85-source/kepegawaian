<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollComponent extends Model { protected $guarded=['id']; protected function casts(): array { return ['amount'=>'decimal:2','percentage'=>'decimal:3','eligibility'=>'array','apply_to_categories'=>'array','is_taxable'=>'boolean','is_bpjs_base'=>'boolean','effective_from'=>'date','effective_to'=>'date','is_active'=>'boolean']; } public function baseComponent(): BelongsTo { return $this->belongsTo(self::class,'base_component_id'); } public function rates(): HasMany { return $this->hasMany(PayrollComponentRate::class,'component_id'); } }
