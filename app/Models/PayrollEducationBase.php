<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollEducationBase extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['base_amount'=>'decimal:2','effective_from'=>'date','effective_to'=>'date']; } public function education(): BelongsTo { return $this->belongsTo(Education::class); } }
