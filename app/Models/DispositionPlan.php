<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispositionPlan extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'dispositionplan';
    protected $guarded = [];
}
