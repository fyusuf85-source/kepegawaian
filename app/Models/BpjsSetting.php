<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BpjsSetting extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['rate'=>'decimal:3','base_amount'=>'decimal:2','min_base'=>'decimal:2','max_base'=>'decimal:2','effective_from'=>'date','effective_to'=>'date']; } public function baseComponent(): BelongsTo { return $this->belongsTo(PayrollComponent::class,'base_component_id'); } }
