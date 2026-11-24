<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollComponentRate extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['amount'=>'decimal:2','percentage'=>'decimal:3','effective_from'=>'date','effective_to'=>'date']; } public function component(): BelongsTo { return $this->belongsTo(PayrollComponent::class); } }
