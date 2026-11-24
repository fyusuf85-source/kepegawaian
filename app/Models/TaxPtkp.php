<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaxPtkp extends Model { public $timestamps=false; protected $guarded=['id']; protected function casts(): array { return ['amount'=>'decimal:2','effective_from'=>'date']; } }
