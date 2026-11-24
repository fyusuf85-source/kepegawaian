<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaxBracket extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['min_amount'=>'decimal:2','max_amount'=>'decimal:2','rate'=>'decimal:6','effective_from'=>'date']; } }
