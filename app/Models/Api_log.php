<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Api_log extends Model
{
    use HasFactory;
    protected $table = 'api_log';
    protected $primaryKey = 'id';
}

