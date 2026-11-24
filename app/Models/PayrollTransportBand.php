<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollTransportBand extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['min_km'=>'decimal:2','max_km'=>'decimal:2','amount'=>'decimal:2','effective_from'=>'date','effective_to'=>'date']; } }
