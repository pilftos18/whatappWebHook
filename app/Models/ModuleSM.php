<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleSM extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'module';
}
